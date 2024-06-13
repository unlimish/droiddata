#!/usr/bin/env python3

import os
import re
import yaml

def get_all_app_names():
    with open("stats/known_apks.txt", "r") as f:
        return sorted({line.split("_")[0] for line in f})

def get_last_known_version_from_known_apks(app_name):
    with open("stats/known_apks.txt", "r") as f:
        versions = [re.search(r'(?<={}_)\d+(?=\.)'.format(app_name), line) for line in f if line.startswith(app_name)]
        versions = [int(version.group()) for version in versions if version is not None]
        return str(max(versions)) if versions else None

def has_vercode_operation(app_name):
    return 'VercodeOperation' in yaml.safe_load(open(f"metadata/{app_name}.yml", 'r'))

def get_all_version_codes_in_yaml_file(app_name):
    with open(f"metadata/{app_name}.yml", "r") as f:
        return [build.get('versionCode') for build in yaml.safe_load(f).get('Builds', []) if build.get('versionCode')]

def get_values_between(values, start, end):
    return [value for value in values if start < value < end]

def remove_version_from_yaml_file(app_name, version_code_to_remove):
    metadata_file = f"metadata/{app_name}.yml"
    with open(metadata_file, "r") as f:
        lines = f.readlines()

    updated_lines = []
    skip_lines = False
    for line in lines:
        if line.strip().endswith(f"versionCode: {version_code_to_remove}"):
            skip_lines = True
            updated_lines.pop()  # remove the line "versionName" too
            continue
        if skip_lines:
            if line.strip() == "":
                skip_lines = False
            continue
        updated_lines.append(line)

    with open(metadata_file, "w") as f:
        f.writelines(updated_lines)

def main():
    for app_name in get_all_app_names():
        last_apk_version = get_last_known_version_from_known_apks(app_name)
        if last_apk_version and os.path.exists(f"metadata/{app_name}.yml") and not has_vercode_operation(app_name):
            version_codes = get_all_version_codes_in_yaml_file(app_name)
            last_version_code = version_codes[-1]
            filtered_version_codes = get_values_between(version_codes, int(last_apk_version), last_version_code)
            if filtered_version_codes:
                print(f"Metadata updated for {app_name} with last known APK version {last_apk_version} and last upstream version {last_version_code} - removing versions {filtered_version_codes}")
                for vc in filtered_version_codes:
                    remove_version_from_yaml_file(app_name, vc)

if __name__ == "__main__":
    main()
