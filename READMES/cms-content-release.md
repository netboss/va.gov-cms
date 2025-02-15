# CMS Content Release

Content releases are initiated in one of two ways:
- Automatically, via [this Jenkins job](http://jenkins.vfs.va.gov/job/deploys/job/vets-website-content-autodeploy/)
- Manually, via the ["Release content"](https://prod.cms.va.gov/admin/content/deploy) page in the CMS.

## Automatic

### Timed

The [Content-Only Autodeploy job](http://jenkins.vfs.va.gov/job/deploys/job/vets-gov-autodeploy-content-build/) handles automatic content deploys.

It is currently [set](https://github.com/department-of-veterans-affairs/devops/blob/676833d3d85abad9071e1df71a9c73b9f027bd41/ansible/deployment/config/jenkins-vetsgov/seed_job.groovy#L310) to execute weekdays at 9AM, 10AM, 11AM, 12PM, 1:45PM, 4PM, amd 5PM.

### Triggered

The content release is also triggered based upon content updates in Drupal.  The logic that controls when a build is triggered is at [`Drupal\va_gov_build_trigger\Service::triggerFrontendBuildFromContentSave()`](https://github.com/department-of-veterans-affairs/va.gov-cms/blob/98f4666d7b6aabf984f679fdaec4088c35e08488/docroot/modules/custom/va_gov_build_trigger/src/Service/BuildFrontend.php#L162).

## Manual

Manual content releases are initiated from the "Release content" page in the CMS.

This page is constructed by the [`va_gov_build_trigger`](https://github.com/department-of-veterans-affairs/va.gov-cms/tree/master/docroot/modules/custom/va_gov_build_trigger) module. The page will differ slightly in presentation and significantly in the details of its operation depending on which environment hosts it.

### Build-Release-Deploy (Production and Staging)

The "Release content" page on BRD environments invokes the [same Jenkins job](http://jenkins.vfs.va.gov/job/deploys/job/vets-gov-autodeploy-content-build/) as the automatic deploys. Accordingly the content build output should be identical.

The Jenkins job configuration is stored in Drupal `settings.php`. Here are the settings for [production](https://github.com/department-of-veterans-affairs/va.gov-cms/blob/master/docroot/sites/default/settings/settings.prod.php#L7-L9). Settings for other environments can be found in the `*.settings.php` [files](https://github.com/department-of-veterans-affairs/va.gov-cms/blob/master/docroot/sites/default/settings). The setting keys are:
```php
$settings['jenkins_build_job_host']
$settings['jenkins_build_job_path']
$settings['jenkins_build_job_params']
$settings['jenkins_build_job_url']
```

**To manually run the Jenkins job:**
1. Go to http://jenkins.vfs.va.gov/job/deploys/job/vets-gov-autodeploy-content-build/.
2. Click "Build with parameters" setting the `release_wait` to 0 and make sure `use_latest_release` is selected.

![image](https://user-images.githubusercontent.com/121603/129736319-9cea3bb3-8b8c-445e-8366-54e004b68e2c.png)

[_**More documentation for the content build can be found here**_](https://github.com/department-of-veterans-affairs/va.gov-team/tree/master/platform/cms/accelerated_publishing/content-build).


### Tugboat and Local Development

The Tugboat and local development (Lando, etc) versions of the Release content page do not trigger a Jenkins job.  Instead, they check out the latest version (or a specified branch or release) of the [frontend](https://github.com/department-of-veterans-affairs/content-build/), build it, and then perform a content release.

For more information on creating or releasing content from a preview environment, see [Environments](./environments.md).

[Table of Contents](../README.md)
