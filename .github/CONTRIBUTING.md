# Contributing to CoCart Products ✨

CoCart Products helps power many headless stores across the internet, and your help making it even more awesome will be greatly appreciated :)

There are many ways to contribute to the project!

- [Translating strings into your language](#translating-cocart-products).
- Testing open [issues](https://github.com/co-cart/cocart-products/issues) or [pull requests](https://github.com/co-cart/cocart-products/pulls) and sharing your findings in a comment.
- Testing CoCart Products beta versions and release candidates. Those are announced in the [CoCart development blog](https://cocart.xyz/news/).
- Submitting fixes, improvements, and enhancements.

If you wish to contribute code, please read the information in the sections below. Then [fork](https://help.github.com/articles/fork-a-repo/) CoCart Products, commit your changes, and [submit a pull request](https://help.github.com/articles/using-pull-requests/) 🎉

I use the `good first issue` label to mark issues that are suitable for new contributors. You can find all the issues with this label [here](https://github.com/co-cart/cocart-products/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc+label%3A%22good+first+issue%22).

CoCart Products is licensed under the GPLv3+, and all contributions to the project will be released under the same license. You maintain copyright over any contribution you make, and by submitting a pull request, you are agreeing to release that contribution under the GPLv3+ license.

If you have questions about the process to contribute code or want to discuss details of your contribution, you can contact CoCart core developers on the #core channel in the [CoCart community Slack](https://cocart.xyz/community/).

## Getting started

- [How to set up WooCommerce development environment](https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment)
- [String localization guidelines](#string-localization-guidelines)

## Coding Guidelines and Development 🛠

- Ensure you stick to the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
- Ensure you use LF line endings in your code editor. Use [EditorConfig](http://editorconfig.org/) if your editor supports it so that indentation, line endings and other settings are auto configured.
- When committing, reference your issue number (#1234) and include a note about the fix.
- Ensure that your code supports the minimum supported versions of PHP and WordPress; this is shown at the top of the `readme.txt` file.
- Push the changes to your fork and submit a pull request on the master branch of the CoCart Products repository.
- Make sure to write good and detailed commit messages (see [this post](https://chris.beams.io/posts/git-commit/) for more on this) and follow all the applicable sections of the pull request template.
- Please avoid modifying the changelog directly or updating the .pot files. These will be updated by the CoCart team.

## Translating CoCart Products

CoCart Products can be translated on [translate.cocart.xyz](https://translate.cocart.xyz/projects/cocart-products/)

If CoCart Products is already 100% translated for your language, join the team anyway! Updates to the language files, and new strings that need translation will likely be added from time to time.

## String localization guidelines

 1. Use `cocart-products` textdomain in all strings.
 2. When using dynamic strings in printf/sprintf, if you are replacing > 1 string use numbered args. e.g. `Test %s string %s.` would be `Test %1$s string %2$s.`
 3. Use sentence case. e.g. `Some Thing` should be `Some thing`.
 4. Avoid HTML. If needed, insert the HTML using sprintf.

For more information, see WP core document [i18n for WordPress Developers](https://codex.wordpress.org/I18n_for_WordPress_Developers).
