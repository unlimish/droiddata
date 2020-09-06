**Note:** Apps targeting android sdk 30 (`targetSdk 30`) published through F-Droid currently cannot be installed on Android 11(R) devices.
We are working on a solution for that but for now you might want to consider staying at targetSDK 29. The issue is tracked here: https://gitlab.com/fdroid/fdroidserver/-/issues/827

* [ ] The app complies with the [inclusion criteria](https://f-droid.org/docs/Inclusion_Policy)
* [ ] The original app author has been notified (and supports the inclusion)
* [ ] All related [fdroiddata](https://gitlab.com/fdroid/fdroiddata/issues) and [RFP issues](https://gitlab.com/fdroid/rfp/issues) have been referenced in this merge request
* [ ] The upstream app source code repo contains the app metadata _(summary/description/images/changelog/etc)_ in a [Fastlane](https://gitlab.com/snippets/1895688) or [Triple-T](https://gitlab.com/snippets/1901490) folder structure
* [ ] Builds with `fdroid build`
* [ ] Releases are tagged

---------------------

