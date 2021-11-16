#!/usr/bin/env python3
import os
import sys
import numpy as np

size = int(sys.argv[1])  # [kB]
file_path = f'{os.path.abspath(os.path.dirname(__file__))}/test_data.bin'

np.zeros(size * 1000, np.uint8).tofile(file_path)
os.chmod(file_path, 0o750)
