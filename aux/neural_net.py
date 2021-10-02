import math
import numpy as np
import torch
import torch.nn as nn
import torchmetrics
import matplotlib.pyplot as plt
from tqdm import tqdm

DEVICE = 'cpu'


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

    return tuple([get_output_size(dim) for dim in range(len(input_shape))])


def compute_n_conv_weights(conv):
    return conv.in_channels * conv.out_channels * np.product(conv.kernel_size)


def compute_n_dense_weights(dense):
    return dense.in_features * dense.out_features


class CryNet(nn.Module):
    def __init__(self, input_shape, n_labels, dtype=None):
        super().__init__()
        self.dtype = dtype

        if len(input_shape) == 2:
            n_input_channels = 1
        else:
            assert len(input_shape) == 3
            n_input_channels = input_shape[2]
            input_shape = input_shape[:2]

        self.n_weights = 0

        conv_1 = nn.Conv2d(n_input_channels,
                           32,
                           kernel_size=3,
                           stride=1,
                           padding='same',
                           bias=True,
                           dtype=self.dtype)
        conv_1_output_shape = compute_conv_output_shape(input_shape, conv_1)

        conv_1_max_pool = nn.MaxPool2d(kernel_size=2)
        conv_1_max_pool_output_shape = compute_conv_output_shape(
            conv_1_output_shape, conv_1_max_pool)

        conv_2 = nn.Conv2d(conv_1.out_channels,
                           64,
                           kernel_size=3,
                           stride=1,
                           padding='same',
                           bias=True,
                           dtype=self.dtype)
        conv_2_dropout = nn.Dropout2d(p=0.5)
        conv_2_output_shape = compute_conv_output_shape(
            conv_1_max_pool_output_shape, conv_2)

        conv_2_max_pool = nn.MaxPool2d(kernel_size=2)
        conv_2_max_pool_output_shape = compute_conv_output_shape(
            conv_2_output_shape, conv_2_max_pool)

        flatten = nn.Flatten()
        n_flatten_output_features = np.product(
            conv_2_max_pool_output_shape) * conv_2.out_channels

        dense_1 = nn.Linear(n_flatten_output_features,
                            64,
                            bias=True,
                            dtype=self.dtype)
        dense_1_dropout = nn.Dropout(p=0.5)

        dense_2 = nn.Linear(dense_1.out_features,
                            n_labels,
                            bias=True,
                            dtype=self.dtype)

        relu = nn.ReLU()

        log_softmax = nn.LogSoftmax(dim=1)

        self.layers = nn.Sequential(conv_1, conv_1_max_pool, relu, conv_2,
                                    conv_2_dropout, conv_2_max_pool, relu,
                                    flatten, dense_1, dense_1_dropout, relu,
                                    dense_2, relu, log_softmax)

        self.n_weights = sum(map(
            compute_n_conv_weights, [conv_1, conv_2])) + sum(
                map(compute_n_dense_weights, [dense_1, dense_2]))

    def forward(self, x):
        return self.layers(x)


def create_model(*args, **kwargs):
    model = CryNet(*args, **kwargs)
    return model


def create_optimizer(model, learning_rate=1e-4):
    return torch.optim.Adam(model.parameters(), lr=learning_rate)


def create_loss_function():
    return nn.CrossEntropyLoss()


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


class TrainingVisualizer:
    def __init__(self, metric_collection, max_cols=None):
        self.train_metric = metric_collection
        self.test_metric = metric_collection.clone()

        metric_names = list(metric_collection.keys())
        self.n_axes = len(metric_names)
        if max_cols is None:
            max_cols = int(np.ceil(np.sqrt(self.n_axes)))
        ncols = min(max_cols, self.n_axes)
        nrows = int(np.ceil(self.n_axes / ncols))
        self.fig, self.axes = plt.subplots(ncols=ncols,
                                           nrows=nrows,
                                           squeeze=False)
        self.lines = {}
        for idx, name in enumerate(metric_names):
            self.__create_train_test_lines_plot(name, self.__get_axis(idx))

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

    def append_metric_results(self, epoch, train_metric_results,
                              test_metric_results):
        for mode, result in zip(['train', 'test'],
                                [train_metric_results, test_metric_results]):
            for name, value in result.items():
                if name in self.lines and mode in self.lines[name]:
                    self.__append_to_line_plot(self.lines[name][mode],
                                               epoch,
                                               value,
                                               draw=False)
        self.draw()

    def __get_axis(self, idx):
        return self.axes[np.unravel_index(idx, self.axes.shape)]

    def __create_train_test_lines_plot(self, name, ax):
        self.lines[name] = {}
        self.lines[name]['train'], = ax.plot([], [],
                                             ls='-',
                                             marker='.',
                                             color='tab:orange',
                                             label='Train')
        self.lines[name]['test'], = ax.plot([], [],
                                            ls='-',
                                            marker='.',
                                            color='tab:blue',
                                            label='Test')
        ax.set_xlabel('Epoch')
        ax.set_ylabel(name)
        ax.legend(loc='best')

    def __append_to_line_plot(self, lines, x, y, draw=True):
        lines.set_xdata(np.append(lines.get_xdata(), x))
        lines.set_ydata(np.append(lines.get_ydata(), y))
        if draw:
            self.draw()


if __name__ == '__main__':
    from data import create_dataloaders
    config = dict(batch_size=32, learning_rate=1e-4, n_epochs=1000)
    dataset, train_dataloader, test_dataloader = create_dataloaders(
        batch_size=config['batch_size'])
    model = create_model(dataset.get_feature_shape(), dataset.get_n_labels())
    loss_function = create_loss_function()
    optimizer = create_optimizer(model, learning_rate=config['learning_rate'])
    metric_kwargs = dict(num_classes=3, average='macro', multiclass=True)
    visualizer = TrainingVisualizer(
        torchmetrics.MetricCollection(torchmetrics.Accuracy(**metric_kwargs),
                                      torchmetrics.Precision(**metric_kwargs),
                                      torchmetrics.Recall(**metric_kwargs),
                                      torchmetrics.F1(**metric_kwargs)))

    calculate_initial_metrics(train_dataloader,
                              test_dataloader,
                              model,
                              visualizer.test_metric,
                              visualizer.train_metric,
                              callback=visualizer.append_metric_results)
    train_results, test_results = run_model_training(
        train_dataloader,
        test_dataloader,
        model,
        loss_function,
        optimizer,
        visualizer.test_metric,
        train_metric=visualizer.train_metric,
        n_epochs=config['n_epochs'],
        callback=visualizer.append_metric_results)

    visualizer.stay_awake()
