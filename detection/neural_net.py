import math
import numpy as np
import torch
from torch._C import device
import torch.nn as nn
import torchmetrics
import matplotlib.pyplot as plt
from mpl_toolkits.axes_grid1 import make_axes_locatable
from tqdm import tqdm

DEVICE = 'cuda' if torch.cuda.is_available() else 'cpu'


def compute_conv_output_shape(input_shape, conv):
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
        ((input_shape[dim] + (2 * padding[dim]) -
          (dilation[dim] * (kernel_size[dim] - 1)) - 1) / stride[dim]) + 1)

    return tuple([get_output_size(dim)
                  for dim in range(2)]) + (conv.out_channels if isinstance(
                      conv, nn.Conv2d) else input_shape[2], )


def compute_n_conv_weights(conv):
    return conv.in_channels * conv.out_channels * np.product(conv.kernel_size)


def compute_n_dense_weights(dense):
    return dense.in_features * dense.out_features


class CryNet(nn.Module):
    def __init__(self, input_shape, n_labels, dtype=None):
        super().__init__()
        self.dtype = dtype

        self.n_weights = 0
        self.layers = []

        def add_conv_layer(in_shape,
                           out_channels,
                           kernel_size=3,
                           stride=1,
                           padding='same',
                           bias=True):
            conv = nn.Conv2d(in_shape[2],
                             out_channels,
                             kernel_size=kernel_size,
                             stride=stride,
                             padding=padding,
                             bias=bias,
                             dtype=self.dtype)
            out_shape = compute_conv_output_shape(in_shape, conv)
            self.n_weights += compute_n_conv_weights(conv)
            self.layers.append(conv)
            return out_shape

        def add_max_pool_layer(in_shape, kernel_size=2, stride=2):
            max_pool = nn.MaxPool2d(kernel_size=kernel_size, stride=stride)
            out_shape = compute_conv_output_shape(in_shape, max_pool)
            self.layers.append(max_pool)
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
            return out_features

        def add_dropout_layer(p=0.5):
            dropout = nn.Dropout(p=p)
            self.layers.append(dropout)

        def add_relu_activation():
            self.layers.append(nn.ReLU())

        def add_softmax_activation(dim=1):
            self.layers.append(nn.LogSoftmax(dim=dim))

        if len(input_shape) == 2:
            input_shape = input_shape + (1, )
        else:
            assert len(input_shape) == 3

        shape = add_conv_layer(input_shape, 64)
        shape = add_max_pool_layer(shape)
        add_relu_activation()
        shape = add_conv_layer(shape, 128)
        add_relu_activation()
        shape = add_conv_layer(shape, 128)
        shape = add_max_pool_layer(shape)
        add_relu_activation()
        shape = add_conv_layer(shape, 256)
        add_relu_activation()
        shape = add_conv_layer(shape, 256)
        add_relu_activation()
        size = add_flatten_layer(shape)
        size = add_dense_layer(size, 1024)
        add_relu_activation()
        size = add_dense_layer(size, 1024)
        add_relu_activation()
        size = add_dense_layer(size, 3)
        add_softmax_activation()

        self.layers = nn.Sequential(*self.layers)

    def forward(self, x):
        return self.layers(x)


class TrainingVisualizer:
    def __init__(self,
                 metric_names,
                 label_names,
                 max_cols=None,
                 figsize=(12, 8),
                 dpi=100):
        self.metric_names = metric_names
        self.label_names = label_names

        self.n_axes = len(metric_names)

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

    def draw(self):
        for ax in map(self.__get_axis, range(self.n_axes)):
            ax.relim()
            ax.autoscale_view()
        self.fig.canvas.draw()
        self.fig.canvas.flush_events()
        plt.pause(0.01)

    def stay_awake(self):
        plt.pause(np.inf)

    def update_metric_results(self, epoch, train_metric_results,
                              test_metric_results):
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
        ax.legend(loc='best')

        def updater(epoch, train_data, test_data):
            self.__append_to_line_plot(self.plots[name]['train'],
                                       epoch,
                                       train_data.item(),
                                       draw=False)
            self.__append_to_line_plot(self.plots[name]['test'],
                                       epoch,
                                       test_data.item(),
                                       draw=False)

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
                texts[i, j] = ax.text(i, j, '', ha='center')

        self.plots[name] = dict(im=im, texts=texts)

        def updater(epoch, train_data, test_data):
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
        print(confusion_matrix)
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


def create_optimizer(model, learning_rate=1e-3):
    return torch.optim.Adam(model.parameters(), lr=learning_rate)


def create_loss_function():
    return nn.CrossEntropyLoss()


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


def create_visualizer(dataset, train_metric, test_metric):
    return TrainingVisualizer(
        list(set(train_metric.keys()).union(set(test_metric.keys()))),
        dataset.get_label_names())


def calculate_initial_metrics(train_dataloader,
                              test_dataloader,
                              model,
                              test_metric,
                              train_metric,
                              callback,
                              show_train_progress=True,
                              show_test_progress=True):
    test_model(train_dataloader,
               model,
               train_metric,
               show_progress=show_train_progress)
    test_model(test_dataloader,
               model,
               test_metric,
               show_progress=show_test_progress)
    callback(0, train_metric.compute(), test_metric.compute())


def run_model_training(train_dataloader,
                       test_dataloader,
                       model,
                       loss_function,
                       optimizer,
                       test_metric,
                       n_epochs=1,
                       train_metric=None,
                       callback=None,
                       save_path=None,
                       metric_save_path=None,
                       show_train_progress=True,
                       show_test_progress=True):
    if train_metric is not None:
        train_results = []
    test_results = []

    for epoch in range(1, n_epochs + 1):
        if n_epochs > 1:
            print(f'Starting epoch {epoch}')

        train_model(train_dataloader,
                    model,
                    loss_function,
                    optimizer,
                    metric=train_metric,
                    show_progress=show_train_progress)
        if train_metric is not None:
            train_results.append(train_metric.compute())

        test_model(test_dataloader,
                   model,
                   test_metric,
                   show_progress=show_test_progress)
        test_results.append(test_metric.compute())

        if callback is not None:
            if train_metric is None:
                callback(epoch, test_results[-1])
            else:
                callback(epoch, train_results[-1], test_results[-1])

    if save_path is not None:
        torch.save(model.state_dict(), save_path)

    if metric_save_path is not None:
        metric_data = dict(test=test_results)
        if train_metric is not None:
            metric_data['train'] = train_results
        torch.save(metric_data, metric_save_path)

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
    model.eval()
    metric.reset()

    if show_progress:
        dataloader = tqdm(dataloader)
    with torch.no_grad():
        for examples, labels in dataloader:
            predictions = model(examples)
            metric(predictions, labels)


def print_accuracy(epoch, *accuracies):
    test_accuracy = accuracies[-1]
    print(
        f'Epoch {epoch+1}: {100*test_accuracy:.2f}%{"" if len(accuracies) == 1 else " [{:.2f}%]".format(100*accuracies[0])}'
    )


if __name__ == '__main__':
    from data import create_dataset, create_dataloaders
    config = dict(batch_size=32, learning_rate=1e-4, n_epochs=1000)
    dataset = create_dataset(device=DEVICE)
    train_dataloader, test_dataloader = create_dataloaders(
        dataset, batch_size=config['batch_size'])
    model = create_model(dataset.get_feature_shape(), dataset.get_n_labels())
    loss_function = create_loss_function()
    optimizer = create_optimizer(model, learning_rate=config['learning_rate'])
    train_metric, test_metric = create_metrics()
    visualizer = create_visualizer(dataset, train_metric, test_metric)

    calculate_initial_metrics(train_dataloader,
                              test_dataloader,
                              model,
                              test_metric,
                              train_metric,
                              callback=visualizer.update_metric_results)
    train_results, test_results = run_model_training(
        train_dataloader,
        test_dataloader,
        model,
        loss_function,
        optimizer,
        test_metric,
        train_metric=train_metric,
        n_epochs=config['n_epochs'],
        callback=visualizer.update_metric_results)

    visualizer.stay_awake()
