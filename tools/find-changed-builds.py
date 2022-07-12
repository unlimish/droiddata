#!/usr/bin/env python3

import os
import subprocess
import sys

import yaml
from colorama import Fore, Style

changed = set(os.getenv('CHANGED').split(' '))
changed.discard('')

target_ref = os.getenv('TARGET_REF')
source_ref = os.getenv('SOURCE_REF')

for appid in sorted(changed):
    metadata_file = f'metadata/{appid}.yml'
    diff = subprocess.check_output(
        (
            f'git diff --no-color --diff-filter=d {target_ref}...{source_ref} -- {metadata_file}' 
        ).split(' ')
    )

    with open(metadata_file) as fp:
        current = yaml.safe_load(fp)
    cmd = 'git apply --reverse'
    p = subprocess.run(cmd.split(' '), input=diff, stdout=subprocess.DEVNULL)
    if p.returncode:
        print(
            Fore.RED + ('ERROR: %s: %d' % (cmd, p.returncode)) + Style.RESET_ALL,
            file=sys.stderr,
        )
        sys.exit(p.returncode)

    to_build = []
    if os.path.exists(metadata_file):
        with open(metadata_file) as fp:
            previous = yaml.safe_load(fp)
        cmd = 'git apply'
        p = subprocess.run(cmd.split(' '), input=diff, stdout=subprocess.DEVNULL)
        if p.returncode:
            print(
                Fore.RED + ('ERROR: %s: %d' % (cmd, p.returncode)) + Style.RESET_ALL,
                file=sys.stderr,
            )
            sys.exit(p.returncode)

        previous_builds = dict()
        for build in previous['Builds']:
            previous_builds[build['versionCode']] = build

        for build in current['Builds']:
            vc = build['versionCode']
            if vc not in previous_builds:
                to_build.append(vc)
                continue
            if build != previous_builds[vc]:
                to_build.append(vc)
    else:
        # this is a brand new metadata file
        cmd = 'git checkout -- ' + metadata_file
        p = subprocess.run(cmd.split(' '), stdout=subprocess.DEVNULL)
        if p.returncode:
            print(
                Fore.RED + ('ERROR: %s: %d' % (cmd, p.returncode)) + Style.RESET_ALL,
                file=sys.stderr,
            )
            sys.exit(p.returncode)
        with open(metadata_file) as fp:
            data = yaml.safe_load(fp)
        for build in data['Builds']:
            to_build.append(build['versionCode'])

    signatures_dir = 'metadata/%s/signatures/' % appid
    diff = subprocess.check_output(
        (
            f'git diff --name-only --no-color --diff-filter=d {target_ref}...{source_ref} -- '
            + signatures_dir
        ).split(' ')
    )
    for f in diff.split():
        vc = int(os.path.basename(os.path.dirname(f)))
        if vc not in to_build:
            to_build.append(vc)

    for vc in to_build:
        print('%s:%d' % (appid, vc), end=' ')
