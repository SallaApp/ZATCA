name: 🐛 Bug Report
description: Create a report to help us improve Salla CLI
title: "bug: "
body:
  - type: checkboxes
    attributes:
      label: Prerequisites
      description: Please ensure you have completed the following.
      options:
        - label: I have searched for [existing issues](https://github.com/SallaApp/Salla-CLI/issues) that already report this problem, without success.
          required: true
  - type: checkboxes
    attributes:
      label: SallaCLI Version
      description: Please select which versions of Salla CLI this issue impacts. For Salla CLI 1.x issues, please use https://github.com/SallaApp/Salla-CLI.
      options:
        - label: v1.x
  - type: dropdown
    id: issue_type
    attributes:
      label: Salla CLI issue Type
      description: Please select the project type.
      options:
        - App
        - Theme
    validations:
      required: true
  - type: textarea
    attributes:
      label: Current Behavior
      description: A clear description of what the bug is and how it manifests.
    validations:
      required: true
  - type: textarea
    attributes:
      label: Expected Behavior
      description: A clear description of what you expected to happen.
    validations:
      required: true
  - type: textarea
    attributes:
      label: Steps to Reproduce
      description: Please explain the steps required to duplicate this issue.
    validations:
      required: true
  - type: input
    attributes:
      label: Code Reproduction URL
      description: Please reproduce this issue in a blank Salla CLI starter application and provide a link to the repo. Try out our [Getting Started Wizard](https://salla-dev.webpkgcache.com/doc/-/s/salla.dev/blog/meet-salla-cli/) to quickly spin up an Salla CLI starter app. This is the best way to ensure this issue is triaged quickly. Issues without a code reproduction may be closed if the Ionic Team cannot reproduce the issue you are reporting.
      placeholder: https://github.com/...
  - type: textarea
    attributes:
      label: Salla Info
      description: Please run `salla info` from within your Salla CLI project directory and paste the output below.
    validations:
      required: true
  - type: textarea
    attributes:
      label: Additional Information
      description: List any other information that is relevant to your issue. Stack traces, related issues, suggestions on how to fix, Stack Overflow links, forum links, etc.