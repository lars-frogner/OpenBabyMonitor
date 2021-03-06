import math
import time
import pathlib
import pickle
import collections
import numpy as np
import torch
import torch.nn as nn
import torchmetrics
import matplotlib.pyplot as plt
from mpl_toolkits.axes_grid1 import make_axes_locatable
from tqdm import tqdm

DEVICE = 'cuda' if torch.cuda.is_available() else 'cpu'


def get_conv_same_padding(kernel_size):
    return nn.Conv2d(
        1, 1, kernel_size=kernel_size,
        padding='same')._reversed_padding_repeated_twice[::-1][::2]


def compute_conv_output_shape(input_shape, conv):
    assert len(input_shape) == 3
    kernel_size = conv.kernel_size if isinstance(
        conv.kernel_size, tuple) else (conv.kernel_size, conv.kernel_size)
    if isinstance(conv.padding, str):
        padding = conv._reversed_padding_repeated_twice[::-1][::2]
    else:
        padding = conv.padding if isinstance(
            conv.padding, tuple) else (conv.padding, conv.padding)
    stride = conv.stride if isinstance(conv.stride, tuple) else (conv.stride,
                                                                 conv.stride)
    dilation = conv.dilation if isinstance(
        conv.dilation, tuple) else (conv.dilation, conv.dilation)

    get_output_size = lambda dim: math.floor(
        ((input_shape[dim + 1] + (2 * padding[dim]) -
          (dilation[dim] * (kernel_size[dim] - 1)) - 1) / stride[dim]) + 1)

    return (
        (conv.out_channels, ) if isinstance(conv, nn.Conv2d) else
        (input_shape[0], )) + tuple([get_output_size(dim) for dim in range(2)])


def compute_n_conv_weights(conv):
    return conv.in_channels * conv.out_channels * np.product(conv.kernel_size)


def compute_n_dense_weights(dense):
    return dense.in_features * dense.out_features


class CryNet(nn.Module):
    def __init__(self,
                 input_shape,
                 n_labels,
                 aspect=None,
                 complexity=0,
                 repeats=[1, 1, 1, 1, 1],
                 use_batch_norm=False,
                 use_dropout=False,
                 dtype=None):
        super().__init__()
        self.n_labels = n_labels
        self.aspect = aspect
        self.complexity = complexity
        self.repeats = repeats
        self.use_batch_norm = use_batch_norm
        self.use_dropout = use_dropout
        self.dtype = dtype

        self.n_weights = 0
        self.layers = []
        self.description = ''

        def add_conv_layer(in_shape,
                           out_channels,
                           kernel_size=3,
                           stride=1,
                           padding='same',
                           bias=True):
            conv = nn.Conv2d(in_shape[0],
                             out_channels,
                             kernel_size=kernel_size,
                             stride=stride,
                             padding=(get_conv_same_padding(kernel_size)
                                      if padding == 'same' else padding),
                             bias=bias,
                             dtype=self.dtype)
            out_shape = compute_conv_output_shape(in_shape, conv)
            self.n_weights += compute_n_conv_weights(conv)
            self.layers.append(conv)
            self.description += f'c{out_channels}'
            return out_shape

        def add_max_pool_layer(in_shape, kernel_size=2, stride=2):
            max_pool = nn.MaxPool2d(kernel_size=kernel_size, stride=stride)
            out_shape = compute_conv_output_shape(in_shape, max_pool)
            self.layers.append(max_pool)
            self.description += 'mp'
            return out_shape

        def add_flatten_layer(in_shape):
            flatten = nn.Flatten()
            out_size = np.product(in_shape)
            self.layers.append(flatten)
            return out_size

        def add_dense_layer(in_features, out_features, bias=True):
            dense = nn.Linear(in_features,
                              out_features,
                              bias=bias,
                              dtype=self.dtype)
            self.n_weights += compute_n_dense_weights(dense)
            self.layers.append(dense)
            self.description += f'd{out_features}'
            return out_features

        def add_conv_batch_normalization_layer(out_shape):
            if use_batch_norm:
                batch_norm = nn.BatchNorm2d(out_shape[0])
                self.layers.append(batch_norm)
                self.description += 'bn'

        def add_dense_batch_normalization_layer(out_size):
            if use_batch_norm:
                batch_norm = nn.BatchNorm1d(out_size)
                self.layers.append(batch_norm)
                self.description += 'bn'

        def add_dropout_layer(p=0.5):
            if use_dropout:
                dropout = nn.Dropout(p=p)
                self.layers.append(dropout)
                self.description += 'do'

        def add_relu_activation():
            self.layers.append(nn.ReLU())

        def add_softmax_activation(dim=1):
            self.layers.append(nn.LogSoftmax(dim=dim))

        if len(input_shape) == 2:
            input_shape = (1, ) + input_shape
        else:
            assert len(input_shape) == 3

        self.input_shape = input_shape

        self.description += f'i={input_shape[1]}x{input_shape[2]}'

        modify = lambda n: int(n * 2**complexity)

        if aspect is None:
            get_stride = lambda: 1
        else:
            assert input_shape[2] % (aspect * input_shape[1]) == 0
            hor_stride = input_shape[2] // (aspect * input_shape[1])
            assert hor_stride % 2 == 0
            assert hor_stride // 2 <= np.product(repeats[:-1])
            initial_strides = [(1, 2)] * (hor_stride // 2)

            def get_stride():
                if len(initial_strides) > 0:
                    return initial_strides.pop()
                else:
                    return 1

        shape = input_shape
        for i in range(repeats[0]):
            shape = add_conv_layer(shape, modify(32), stride=get_stride())
            if i == repeats[0] - 1:
                shape = add_max_pool_layer(shape)
            add_conv_batch_normalization_layer(shape)
            add_relu_activation()

        for i in range(repeats[1]):
            shape = add_conv_layer(shape, modify(64), stride=get_stride())
            if i == repeats[1] - 1:
                shape = add_max_pool_layer(shape)
            add_conv_batch_normalization_layer(shape)
            add_relu_activation()

        for i in range(repeats[2]):
            shape = add_conv_layer(shape, modify(128), stride=get_stride())
            if i == repeats[2] - 1:
                shape = add_max_pool_layer(shape)
            add_conv_batch_normalization_layer(shape)
            add_relu_activation()

        for i in range(repeats[3]):
            shape = add_conv_layer(shape, modify(256), stride=get_stride())
            if i == repeats[3] - 1:
                shape = add_max_pool_layer(shape)
            add_conv_batch_normalization_layer(shape)
            add_relu_activation()

        size = add_flatten_layer(shape)

        for i in range(repeats[4]):
            size = add_dense_layer(size, modify(512))
            add_dense_batch_normalization_layer(size)
            add_dropout_layer()
            add_relu_activation()

        size = add_dense_layer(size, n_labels)
        add_softmax_activation()

        self.layers = nn.Sequential(*self.layers)

    def forward(self, x):
        return self.layers(x)


class TrainingVisualizer:
    def __init__(self,
                 metric_names,
                 label_names,
                 max_cols=None,
                 run_params=None,
                 figsize=(12, 8),
                 dpi=100):
        self.metric_names = sorted(metric_names)
        self.label_names = label_names
        self.figure_name = None if run_params is None else self.create_figure_name(
            run_params)

        self.n_axes = len(self.metric_names)

        if max_cols is None:
            max_cols = int(np.ceil(np.sqrt(self.n_axes)))
        ncols = min(max_cols, self.n_axes)
        nrows = int(np.ceil(self.n_axes / ncols))
        self.fig, self.axes = plt.subplots(figsize=figsize,
                                           dpi=dpi,
                                           ncols=ncols,
                                           nrows=nrows,
                                           squeeze=False)

        self.updaters = {}
        self.plots = {}

        confusion_matrix_name = 'ConfusionMatrix'
        if confusion_matrix_name in self.metric_names:
            self.updaters[
                confusion_matrix_name] = self.__create_confusion_matrix_plot_updater(
                    confusion_matrix_name,
                    self.__get_axis(
                        self.metric_names.index(confusion_matrix_name)))

        for idx, name in enumerate(self.metric_names):
            if name == confusion_matrix_name:
                continue
            self.updaters[name] = self.__create_train_test_lines_plot_updater(
                name, self.__get_axis(idx))

        self.fig.tight_layout()
        plt.ion()

    def close(self):
        plt.close(fig=self.fig)

    def create_figure_name(self, run_params):
        return '_'.join(
            [f'{name}={value}'
             for name, value in sorted(run_params.items())]) + '_training.png'

    def draw(self):
        for ax in map(self.__get_axis, range(self.n_axes)):
            ax.relim()
            ax.autoscale_view()
        self.fig.canvas.draw()
        self.fig.canvas.flush_events()
        plt.pause(0.01)

        if self.figure_name is not None:
            try:
                with open(self.figure_name, 'wb') as f:
                    plt.savefig(f)
            except Exception as e:
                print(e)

    def save(self, output_path):
        with open(output_path, 'wb') as f:
            plt.savefig(f)

    def stay_awake(self):
        plt.pause(np.iinfo(np.int32).max)

    def update_metric_results(self, epoch, train_metric_results,
                              test_metric_results):
        if test_metric_results is None:
            test_metric_results = []
        for name in self.metric_names:
            train_results = train_metric_results[name].cpu(
            ) if name in train_metric_results else None
            test_results = test_metric_results[name].cpu(
            ) if name in test_metric_results else None
            self.updaters[name](epoch, train_results, test_results)
        self.draw()

    def __get_axis(self, idx):
        return self.axes[np.unravel_index(idx, self.axes.shape)]

    def __create_train_test_lines_plot_updater(self, name, ax):
        self.plots[name] = {}
        self.plots[name]['train'], = ax.plot([], [],
                                             ls='-',
                                             marker='.',
                                             color='tab:orange',
                                             label='Train')
        self.plots[name]['test'], = ax.plot([], [],
                                            ls='-',
                                            marker='.',
                                            color='tab:blue',
                                            label='Test')
        ax.set_xlabel('Epoch')
        ax.set_ylabel(name)
        ax.legend(loc='lower right')
        text = ax.text(0.02,
                       0.98,
                       '',
                       ha='left',
                       va='top',
                       transform=ax.transAxes)

        def updater(epoch, train_data, test_data):
            self.__append_to_line_plot(self.plots[name]['train'],
                                       epoch,
                                       train_data.item(),
                                       draw=False)
            if test_data is not None:
                self.__append_to_line_plot(self.plots[name]['test'],
                                           epoch,
                                           test_data.item(),
                                           draw=False)
                text.set_text(f'{name} = {test_data.item()*100:.1f} %')

        return updater

    def __append_to_line_plot(self, lines, x, y, draw=True):
        lines.set_xdata(np.append(lines.get_xdata(), x))
        lines.set_ydata(np.append(lines.get_ydata(), y))
        if draw:
            self.draw()

    def __create_confusion_matrix_plot_updater(self, name, ax):
        n_classes = len(self.label_names)

        im = ax.imshow(np.zeros((n_classes, n_classes)),
                       vmin=0.0,
                       vmax=1.0,
                       interpolation='nearest',
                       cmap=plt.get_cmap('Blues'))

        divider = make_axes_locatable(ax)
        cax = divider.append_axes('right', size='5%', pad=0.05)
        self.fig.colorbar(im, cax=cax, orientation='vertical')

        tick_marks = np.arange(n_classes)
        ax.set_xticks(tick_marks)
        ax.set_xticklabels(self.label_names, rotation=45)
        ax.set_yticks(tick_marks)
        ax.set_yticklabels(self.label_names)

        ax.set_xlabel('Predicted class')
        ax.set_ylabel('True class')

        texts = np.empty((n_classes, n_classes), dtype=object)
        for i in range(n_classes):
            for j in range(n_classes):
                texts[i, j] = ax.text(j, i, '', ha='center')

        self.plots[name] = dict(im=im, texts=texts)

        def updater(epoch, train_data, test_data):
            if test_data is not None:
                self.__update_confusion_matrix_plot(self.plots[name]['im'],
                                                    self.plots[name]['texts'],
                                                    test_data.numpy(),
                                                    draw=False)

        return updater

    def __update_confusion_matrix_plot(self,
                                       im,
                                       texts,
                                       confusion_matrix,
                                       draw=True):
        im.set_data(confusion_matrix)
        for i in range(confusion_matrix.shape[0]):
            for j in range(confusion_matrix.shape[1]):
                texts[i, j].set_text(f'{confusion_matrix[i, j]:.2f}')
                texts[i, j].set_color(
                    'white' if confusion_matrix[i, j] > 0.5 else 'black')
        if draw:
            self.draw()


def create_model(*args, **kwargs):
    model = CryNet(*args, **kwargs).to(DEVICE)
    return model


def create_optimizer(model, learning_rate=1e-3, weight_decay=1e-2):
    return torch.optim.AdamW(model.parameters(),
                             lr=learning_rate,
                             weight_decay=weight_decay)


def create_loss_function():
    return nn.CrossEntropyLoss()


class EarlyStopper:
    def __init__(self, num_classes, patience=15):
        self.patience = patience
        self.metric = torchmetrics.ConfusionMatrix(num_classes,
                                                   normalize='true').to(DEVICE)
        self.accuracies = []
        self.last_epoch = None
        self.best_epoch = None
        self.best_accuracy = -np.inf

    def reset(self):
        self.metric.reset()

    def __call__(self, predictions, labels):
        self.metric(predictions, labels)

    def compute(self):
        confusion_matrix = self.metric.compute().cpu()
        lowest_accuracy = np.diagonal(confusion_matrix).min()

        self.last_epoch = len(self.accuracies)
        self.accuracies.append(lowest_accuracy)

        if lowest_accuracy > self.best_accuracy:
            is_best_so_far = True
            self.best_accuracy = lowest_accuracy
            self.best_epoch = self.last_epoch
        else:
            is_best_so_far = False

        print(self.last_epoch, confusion_matrix, lowest_accuracy,
              self.best_accuracy, self.best_epoch)

        return is_best_so_far

    def has_data(self):
        return len(self.accuracies) > 0

    def should_stop(self):
        if self.has_data():
            return self.last_epoch - self.best_epoch >= self.patience
        else:
            return False

    def print_result(self):
        if self.has_data():
            print(
                f'Best minimal accuracy: {self.best_accuracy*100:.2f} % in epoch {self.best_epoch}'
            )


def create_early_stopper(patience=15):
    return EarlyStopper(3, patience=patience)


def create_metrics():
    metric_kwargs = dict(num_classes=3, average='macro', multiclass=True)
    train_metric = torchmetrics.MetricCollection(
        torchmetrics.Accuracy(**metric_kwargs),
        torchmetrics.Precision(**metric_kwargs),
        torchmetrics.Recall(**metric_kwargs),
        torchmetrics.Specificity(**metric_kwargs),
        torchmetrics.F1(**metric_kwargs))

    test_metric = train_metric.clone()
    test_metric.add_metrics(
        torchmetrics.ConfusionMatrix(metric_kwargs['num_classes'],
                                     normalize='true'))

    return train_metric.to(DEVICE), test_metric.to(DEVICE)


def create_visualizer(dataset, train_metric, test_metric, run_params=None):
    return TrainingVisualizer(list(
        set(train_metric.keys()).union(set(test_metric.keys()))),
                              dataset.get_label_names(),
                              run_params=run_params)


def calculate_initial_metrics(train_dataloader,
                              test_dataloader,
                              model,
                              early_stopper,
                              test_metric,
                              train_metric,
                              callback,
                              show_train_progress=True,
                              show_test_progress=True):
    test_model(train_dataloader,
               model,
               train_metric,
               show_progress=show_train_progress)
    test_model_with_early_stopper(test_dataloader,
                                  model,
                                  early_stopper,
                                  test_metric,
                                  show_progress=show_test_progress)
    if test_dataloader is not None:
        early_stopper.compute()
        test_metric_results = test_metric.compute()
    else:
        test_metric_results = None
    callback(0, train_metric.compute(), test_metric_results)


def run_model_training(train_dataloader,
                       test_dataloader,
                       model,
                       loss_function,
                       optimizer,
                       early_stopper,
                       test_metric,
                       max_epochs=1000,
                       train_metric=None,
                       callback=None,
                       output_path=None,
                       metric_output_path=None,
                       show_train_progress=True,
                       show_test_progress=True):
    if train_metric is not None:
        train_results = {}
        for name in train_metric.keys():
            train_results[name] = []

    test_results = {}
    for name in test_metric.keys():
        test_results[name] = []

    for epoch in range(1, max_epochs + 1):
        print(f'Starting epoch {epoch}')

        train_model(train_dataloader,
                    model,
                    loss_function,
                    optimizer,
                    metric=train_metric,
                    show_progress=show_train_progress)

        if train_metric is not None:
            train_result = train_metric.compute()
            for name, value in train_result.items():
                if name == 'ConfusionMatrix':
                    train_results[name].append(value.cpu().numpy())
                else:
                    train_results[name].append(value.cpu().item())

        test_model_with_early_stopper(test_dataloader,
                                      model,
                                      early_stopper,
                                      test_metric,
                                      show_progress=show_test_progress)

        if test_dataloader is not None:
            is_best_so_far = early_stopper.compute()
            if is_best_so_far and output_path is not None:
                save_model(model, output_path)

            test_result = test_metric.compute()
            for name, value in test_result.items():
                if name == 'ConfusionMatrix':
                    test_results[name].append(value.cpu().numpy())
                else:
                    test_results[name].append(value.cpu().item())
        else:
            test_result = None

        if callback is not None:
            if train_metric is None:
                callback(epoch, test_result)
            else:
                callback(epoch, train_result, test_result)

        if early_stopper.should_stop():
            early_stopper.print_result()
            break

    if test_dataloader is None and output_path is not None:
        save_model(model, output_path)

    if metric_output_path is not None:
        metric_data = dict(test=test_results)
        if train_metric is not None:
            metric_data['train'] = train_results
        with open(metric_output_path, 'wb') as f:
            pickle.dump(metric_data, f)

    if train_metric is None:
        return test_results
    else:
        return train_results, test_results


def train_model(dataloader,
                model,
                loss_function,
                optimizer,
                metric=None,
                show_progress=True):
    model.train()
    if metric is not None:
        metric.reset()

    if show_progress:
        dataloader = tqdm(dataloader)
    for examples, labels in dataloader:
        optimizer.zero_grad()

        predictions = model(examples)
        loss = loss_function(predictions, labels)
        loss.backward()

        optimizer.step()

        if metric is not None:
            metric(predictions, labels)


def test_model(dataloader, model, metric, show_progress=True):
    if dataloader is None:
        return

    model.eval()
    metric.reset()

    if show_progress:
        dataloader = tqdm(dataloader)
    with torch.no_grad():
        for examples, labels in dataloader:
            predictions = model(examples)
            metric(predictions, labels)


def test_model_inference_time(dataloader, model):
    model.eval()
    with torch.no_grad():
        start_time = time.time()
        for examples, _ in dataloader:
            model(examples)
        print(
            f'Inference time: {1e3*(time.time() - start_time)/len(dataloader):.2f} ms'
        )


def test_model_with_early_stopper(dataloader,
                                  model,
                                  early_stopper,
                                  metric,
                                  show_progress=True):
    if dataloader is None:
        return

    model.eval()
    early_stopper.reset()
    metric.reset()

    if show_progress:
        dataloader = tqdm(dataloader)
    with torch.no_grad():
        for examples, labels in dataloader:
            predictions = model(examples)
            early_stopper(predictions, labels)
            metric(predictions, labels)


def print_accuracy(epoch, *accuracies):
    test_accuracy = accuracies[-1]
    print(
        f'Epoch {epoch+1}: {100*test_accuracy:.2f}%{"" if len(accuracies) == 1 else " [{:.2f}%]".format(100*accuracies[0])}'
    )


def export_model(model, output_path='model.onnx'):
    model.to('cpu')
    model.eval()
    dummy_feature = torch.zeros(1, *model.input_shape)
    torch.onnx.export(model, dummy_feature, output_path)


def test_model_variations(result_file, aspects, complexities, repeats,
                          batch_sizes, learning_rates, weight_decays,
                          use_batch_norms):

    dataset = create_dataset(device=DEVICE)

    results_path = pathlib.Path(result_file)
    if results_path.exists():
        with open(results_path, 'rb') as f:
            results = pickle.load(f)
    else:
        results = []

    for aspect in aspects:
        for complexity in complexities:
            for repeat in repeats:
                for batch_size in batch_sizes:
                    for learning_rate in learning_rates:
                        for weight_decay in weight_decays:
                            for use_batch_norm in use_batch_norms:

                                params = dict(aspect=aspect,
                                              cplx=complexity,
                                              rep=repeat,
                                              bs=batch_size,
                                              lr=learning_rate,
                                              wd=weight_decay,
                                              bn=use_batch_norm)
                                print(params)

                                if len(
                                        list(
                                            filter(
                                                lambda r: r['params'] ==
                                                params, results))) > 0:
                                    print('Skipping')
                                    continue

                                config = dict(bs=batch_size,
                                              lr=learning_rate,
                                              wd=weight_decay)

                                train_dataloader, test_dataloader = create_dataloaders(
                                    dataset,
                                    batch_size=config['bs'],
                                    num_workers=0)

                                model = create_model(
                                    dataset.get_feature_shape(),
                                    dataset.get_n_labels(),
                                    aspect=aspect,
                                    complexity=complexity,
                                    repeats=repeat,
                                    use_batch_norm=use_batch_norm)

                                loss_function = create_loss_function()

                                optimizer = create_optimizer(
                                    model,
                                    learning_rate=config['lr'],
                                    weight_decay=config['wd'])

                                early_stopper = create_early_stopper()

                                train_metric, test_metric = create_metrics()

                                config['m'] = model.description
                                visualizer = create_visualizer(
                                    dataset,
                                    train_metric,
                                    test_metric,
                                    run_params=config)

                                calculate_initial_metrics(
                                    train_dataloader,
                                    test_dataloader,
                                    model,
                                    early_stopper,
                                    test_metric,
                                    train_metric,
                                    callback=visualizer.update_metric_results)

                                torch.cuda.empty_cache()

                                train_results, test_results = run_model_training(
                                    train_dataloader,
                                    test_dataloader,
                                    model,
                                    loss_function,
                                    optimizer,
                                    early_stopper,
                                    test_metric,
                                    train_metric=train_metric,
                                    callback=visualizer.update_metric_results)

                                visualizer.close()

                                torch.cuda.empty_cache()

                                if len(
                                        list(
                                            filter(
                                                lambda r: r['params'] ==
                                                params, results))) == 0:
                                    results.append(
                                        dict(params=params,
                                             train_results=train_results,
                                             test_results=test_results))
                                    with open(results_path, 'wb') as f:
                                        pickle.dump(results, f)

    print(find_best_model(results_path))


def find_best_model(result_file):
    with open(result_file, 'rb') as f:
        results = pickle.load(f)

    best_minimal_accuracy = -np.inf
    best_model_idx = None
    best_epoch = None
    for model_idx, result in enumerate(results):
        queue = collections.deque(maxlen=6)
        for epoch_idx, matrix in enumerate(
                result['test_results']['ConfusionMatrix']):
            minimal_accuracy = np.diagonal(matrix).min()
            queue.append(minimal_accuracy)
            mean_minimal_accuracy = np.mean(queue)
            if mean_minimal_accuracy > best_minimal_accuracy:
                best_minimal_accuracy = mean_minimal_accuracy
                best_model_idx = model_idx
                best_epoch = epoch_idx + 1 - queue.maxlen // 2
    return best_model_idx, best_epoch, best_minimal_accuracy, results[
        best_model_idx]['params']


def save_model(model, output_path, epoch=None):
    model.eval()

    output_path = pathlib.Path(output_path)
    torch.save(model.state_dict(), output_path)

    params = dict(args=[model.input_shape, model.n_labels],
                  kwargs=dict(aspect=model.aspect,
                              complexity=model.complexity,
                              repeats=model.repeats,
                              use_batch_norm=model.use_batch_norm,
                              use_dropout=model.use_dropout,
                              dtype=model.dtype))
    if epoch is not None:
        params['epoch'] = epoch
    with open(output_path.with_stem(f'{output_path.stem}_params'), 'wb') as f:
        pickle.dump(params, f)


def load_model(output_path):
    output_path = pathlib.Path(output_path)
    state_dict = torch.load(output_path)

    with open(output_path.with_stem(f'{output_path.stem}_params'), 'rb') as f:
        params = pickle.load(f)

    model = CryNet(*params['args'], **params['kwargs'])
    model.load_state_dict(state_dict)
    return model.to(DEVICE)


if __name__ == '__main__':
    from data import create_dataset, create_dataloaders, create_kfold_dataloaders

    model_name = 'crynet'
    max_epochs = 30
    patience = np.inf

    aspect = None
    complexity = 0
    repeats = [1, 1, 2, 2, 1]
    use_batch_norm = True

    config = dict(batch_size=32, learning_rate=3e-4, weight_decay=1e-2)

    dataset = create_dataset(device=DEVICE)

    evaluate = False
    timing = True
    export_to_onnx = True

    n_splits = 1

    if n_splits > 1:
        dataloaders = create_kfold_dataloaders(dataset,
                                               n_splits=n_splits,
                                               batch_size=config['batch_size'],
                                               num_workers=0)
    else:
        train_dataloader, test_dataloader = create_dataloaders(
            dataset,
            test_proportion=0,
            batch_size=config['batch_size'],
            num_workers=0)

    for k in range(n_splits):

        if n_splits > 1:
            train_dataloader, test_dataloader = dataloaders[k]
            full_model_name = f'{model_name}_{k:d}'
        else:
            full_model_name = model_name

        train_metric, test_metric = create_metrics()

        if evaluate:
            model = load_model(f'{full_model_name}.pickle')

            if timing:
                test_model_inference_time(train_dataloader, model)
            else:
                test_model(test_dataloader, model, test_metric)
                print(test_metric.compute())

            if export_to_onnx:
                export_model(model, output_path=f'{full_model_name}.onnx')
        else:
            model = create_model(dataset.get_feature_shape(),
                                 dataset.get_n_labels(),
                                 aspect=aspect,
                                 complexity=complexity,
                                 repeats=repeats,
                                 use_batch_norm=use_batch_norm)

            loss_function = create_loss_function()

            optimizer = create_optimizer(model,
                                         learning_rate=config['learning_rate'],
                                         weight_decay=config['weight_decay'])

            early_stopper = create_early_stopper(patience=patience)

            visualizer = create_visualizer(dataset,
                                           train_metric,
                                           test_metric,
                                           run_params=config)

            calculate_initial_metrics(
                train_dataloader,
                test_dataloader,
                model,
                early_stopper,
                test_metric,
                train_metric,
                callback=visualizer.update_metric_results)

            train_results, test_results = run_model_training(
                train_dataloader,
                test_dataloader,
                model,
                loss_function,
                optimizer,
                early_stopper,
                test_metric,
                train_metric=train_metric,
                max_epochs=max_epochs,
                callback=visualizer.update_metric_results,
                output_path=f'{full_model_name}.pickle',
                metric_output_path=f'{full_model_name}_metrics.pickle')

            visualizer.save(f'{full_model_name}.png')

            visualizer.close()
