#!/usr/bin/env python3
#
# This script lists the URLs of all open merge requests created by
# checkupdates-bot.  To include private merge requests, supply a
# GitLab API Access Token (read-only or higher) from an account that
# is part of the @fdroid group.

import gitlab
import os
from colorama import Fore, Style


def main():
    # default token is from fdroid-anyone
    private_token = os.getenv("PERSONAL_ACCESS_TOKEN", "glpat-2Csb3MWMscuFz1FN_wfj")
    if not private_token:
        print(
            Fore.RED
            + "ERROR: GitLab Token not found in PERSONAL_ACCESS_TOKEN!"
            + Style.RESET_ALL
        )
        sys.exit(1)
    gl = gitlab.Gitlab("https://gitlab.com", api_version=4, private_token=private_token)
    project = gl.projects.get("fdroid/fdroiddata", lazy=True)

    urls = set()
    for page in [0]:  # TODO this should look through all pages
        for mr in project.mergerequests.list(
            per_page=100, page=page, state="opened", order_by="updated_at"
        ):
            if mr.author["username"] == "checkupdates-bot":
                urls.add(mr.web_url)

    for url in sorted(urls, reverse=True):
        print(url, end=" ")
    print()


if __name__ == "__main__":
    main()
