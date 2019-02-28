#!/usr/bin/env python3

import glob
import os

os.chdir(os.path.dirname(__file__) + '/../')

for f in glob.glob('metadata/*/*/*.txt'):
    if os.path.getsize(f) == 0:
        os.remove(f)
        continue

    with open(f) as fp:
        data = fp.read()
    with open(f, 'w') as fp:
        fp.write(data.strip().rstrip())
        fp.write('\n')
