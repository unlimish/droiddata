# Contributing to F-Droid Data

For information about all aspects of F-Droid, check out the [documentation](https://f-droid.org/docs).


## Issue Tracker

### Inclusion of new apps

If you are a "first time" contributor, consider opening an issue at
[RFP (Request for Packaging)](https://gitlab.com/fdroid/rfp/-/issues).
Don't forget to fill in the issue template.

### Updating apps already in F-Droid

You may use the [issue tracker](https://gitlab.com/fdroid/fdroiddata/issues) to report
issues on app metadata or issues with the packages distributed through our repository.
For instance:
- an app is outdated
- an app has Antifeatures, but they are not listed in the app or on the website.

Before opening an issue about an outdated app, have a look at its metadata
file and make sure that updating the app is actually possible. For example,
`MaintainerNotes` might contain a hint on why it's impossible to further update
an app (e.g. proprietary dependencies were added, essential parts of the code
are no longer available).

Also make sure having checked with already existing issues (including closed ones)
which might give similar explanations.

## Merge Requests

### Setting up fdroiddata for merge requests

- [Register on GitLab](https://gitlab.com/).
- Visit and fork the [fdroiddata repository](https://gitlab.com/fdroid/fdroiddata).

App metadata for a merge request can be created without or with the use of `fdroidserver`.
The latter is only recommended for really advanced users.

### Metadata preparation without fdroidserver

You can either write the metadata file locally, on your machine, or directly on the GitLab website.
Please read the [General Recommendations](#general-recommendations-without-fdroidserver) before you start.

#### On the GitLab website

1. Visit your fork.
1. Create a new branch.
   Naming it like the app name or, much better, the app id makes it easier to keep track of your contributions.
1. Visit the `metadata` directory of your fork.
1. Add a new file by clicking on the plus sign and choosing _"New file"_.
1. Set the file name in the following schema: `<application_id>.yml`. So an example would be _"com.app.example.yml"_.
1. Write down the metadata. The [Build Metadata Reference](https://f-droid.org/en/docs/Build_Metadata_Reference)
   as well as the [templates from the wiki](https://gitlab.com/fdroid/wiki/-/wikis/Metadata/YAML-Metadata)
   will help you.
1. Choose a smart commit message and commit your changes.
1. Continue with the [Common steps for both methods](#common-steps-for-both-methods)

#### On your local machine

1. Clone your fork.
1. Create and checkout a new branch.
   Naming it like the app name or, much better, the app id makes it easier to keep track of your contributions.
1. Create a new file in the the `metadata` directory named after the following schema: `<application_id>.yml`.
   So an example would be _"com.app.example"_.
1. Write down the metadata in that file. The [Build Metadata Reference](https://f-droid.org/en/docs/Build_Metadata_Reference)
   as well as the [templates from the wiki](https://gitlab.com/fdroid/wiki/-/wikis/Metadata/YAML-Metadata)
   will help you.
1. Commit and push to your upstream fork.
1. Continue with the [Common steps for both methods](#common-steps-for-both-methods)

#### Common steps for both methods

1. Go to the `CI/CD` menu in the GitLab project of your fork.
1. Check if the pipeline for your commit(s) succeed.
   If not, take a look into the logs and try to fix the error by editing the metadata file again.
1. If everything went fine, you can create a
   [new merge request](https://gitlab.com/fdroid/fdroiddata/-/merge_requests/new) at the `fdroiddata` repository.
1. Now wait for the packagers to pick up your Merge Request. Please keep track if they asked any questions
   and reply to them as soon as possible.

#### General recommendations (without fdroidserver)

- Keep a separate branch for every app you want to submit.
- Keep your forks `master` branch up-to-date. For more information see here:
  https://about.gitlab.com/blog/2016/12/01/how-to-keep-your-fork-up-to-date-with-its-origin
- As a result of the two tips above, you should not commit to your `master` branch.
  This will also trigger new pipelines at the right time.
- Do not open a Merge Request from a protected branch.
  The `master` branch is protected by default, but other branches can also be protected.
- Please fill the template when opening a new Merge Request.

#### Final thoughts

_"How long does it take for my app to show up in the F-Droid repository?"_ is a very frequently asked question.
Please take a look at
[the wiki](https://gitlab.com/fdroid/wiki/-/wikis/FAQ#how-long-does-it-take-for-my-app-to-show-up-on-website-and-client)
for the answer.

### Metadata preparation with fdroidserver

#### Setting up fdroidserver

> Note that to use the `master` branch of _fdroiddata_ you will need the
master branch of _fdroidserver_. Using the latest stable release of
fdroidserver would probably work, but it is not guaranteed.

Install [fdroidserver](https://gitlab.com/fdroid/fdroidserver), or just
use it directly from master:
```shell
git clone https://gitlab.com/fdroid/fdroidserver.git
export PATH="$PATH:$PWD/fdroidserver"
```

Clone your fork of fdroiddata and enter it:
```shell
git clone https://gitlab.com/YOUR_USERNAME/fdroiddata.git
cd fdroiddata
```

Optionally create a base config.py and signing keys with:
```shell
fdroid init
```

Make sure fdroid works and reads the metadata files properly:
```shell
fdroid readmeta
```

#### Adding a new app

If you want to add a new app you will have to add a new metadata file to the
repository, like `metadata/app.id.yml`. Here is how to write that file.

If the app is on GitHub, GitLab or Bitbucket, use `fdroid import`:
```shell
fdroid import --url https://github.com/foo/bar --subdir app
```

Alternatively, start the metadata file from scratch, see [the templates](https://gitlab.com/fdroid/fdroiddata/tree/master/templates):
```shell
cp templates/app-full metadata/app.id.yml
```

Or by download:
```shell
wget -O metadata/app.id.yml https://gitlab.com/fdroid/fdroiddata/raw/master/templates/app-full
```

Now that the file is created, you need to fill up all the app information and
add a working build recipe.

You can use the [metadata section](https://f-droid.org/docs/Build_Metadata_Reference)
in the documentation for reference, or the full template at `templates/app-full` for
some suggestions.

Once you're done, see if `fdroid readmeta` runs without any errors. If it
doesn't, there are syntax errors in your metadata file.

#### Building it

We build apps from source, so a new app must have at least one working build.

You can have a look at the build templates at `templates/build-*` for some
quick suggestions. You may also follow the documentation or look at how other apps
are built for working examples.

- Run `fdroid readmeta` again to make sure there still aren't any syntax errors
- Run `fdroid rewritemeta app.id` to clean up your file
- Run `fdroid checkupdates app.id` to fill automated fields like `Auto Name` and `Current Version`
- Make sure that `fdroid lint app.id` doesn't report any warnings. If it does, fix them.
- Test your build recipe with `fdroid build -v -l app.id`

Congratulations! You can now open a merge request to add your app.

_"How long does it take for my app to show up in the F-Droid repository?"_ is a very frequently asked question.
Please take a look at
[the wiki](https://gitlab.com/fdroid/wiki/-/wikis/FAQ#how-long-does-it-take-for-my-app-to-show-up-on-website-and-client)
for the answer.

#### General recommendations (with fdroidserver)

- [Squash](https://docs.gitlab.com/ee/user/project/merge_requests/squash_and_merge.html) your commits.

- Make sure you've ran `fdroid lint` and `fdroid rewritemeta` before opening a
  merge request.

- If you haven't tested your build, say so in the merge request.

- Check for CI errors once you have opened your Merge Request.

- Please don't commit directly onto your `master` branch. Use a new branch instead. This makes merging much more easy, and the correct jobs in CI will run.


### After You Added Your App

- Once your app metadata is merged, it can take a day or more for the
    build system to build the app.
- If you have enabled [auto-updates], F-Droid will build new versions from tags
    automatically.

    ```
    AutoUpdateMode: Version v%v
    UpdateCheckMode: Tags
    ```
- You may like to add [localization and screenshots], so users can have a glance
    at the app in pictures and in their preferred language.
- You can advertise the download of your app in this app store using the
    [official graphic][get-it-on-fdroid].
    <img src="https://fdroid.gitlab.io/artwork/badge/get-it-on.png" height="75">

    ```
    <img src="https://fdroid.gitlab.io/artwork/badge/get-it-on.png" height="75">
    ```
- You can add a badge of your apps F-Droid version from [shields.io].
    ![](https://img.shields.io/badge/f--droid-v1.0-blue.svg)
    ```
    https://img.shields.io/f-droid/v/APP.ID.svg
    ```
    You can also include a GitHub release badge to know if your version is
    up to date.
    ![Latest Release](https://img.shields.io/badge/release-v1.0-blue.svg?logo=github)
    ```
    https://img.shields.io/github/release/USER/REPO.svg?logo=github
    ```


[localization and screenshots]: https://f-droid.org/en/docs/All_About_Descriptions_Graphics_and_Screenshots/
[get-it-on-fdroid]: https://f-droid.org/badge/get-it-on.png
[auto-updates]: https://f-droid.org/en/docs/Build_Metadata_Reference/#autoupdatemode
[shields.io]: https://shields.io/#/examples/version
