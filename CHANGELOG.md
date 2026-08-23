# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- `PostBuilder::media()` now routes remote URLs through `uploadByUrl()`, so
  SocialBu fetches the file itself in a single request instead of the file
  downloading through your application before the three-step upload flow. If
  SocialBu cannot fetch the URL (e.g. it is only reachable from your own
  network), the builder falls back to the streaming `upload()` automatically,
  so previously working URLs keep working. Local paths are unchanged.
- `ForbiddenException`'s default message now reads "Forbidden: plan limit
  exceeded or insufficient permissions."

## [1.6.1] - 2026-08-21

### Changed

- Releases are now cut from the Actions tab (`Release` → Run workflow) instead
  of by hand-pushed tags. The workflow runs Pint, PHPStan, and the test suite,
  promotes this file's Unreleased section to the new version, commits, tags,
  and publishes the GitHub release with those notes. The version is computed
  from the latest tag by the chosen semver bump, or set explicitly via the
  version field.
- GitHub Actions checkout updated to v5, clearing the Node 20 deprecation
  warnings on every CI job.

## [1.6.0] - 2026-08-21

### Added

- PHPStan static analysis at level 8 with larastan, with no baseline. Runs in
  CI alongside Pint and the test matrix. Locally: `composer analyse`.
- `ForbiddenException`, thrown on 403 responses. SocialBu returns 403 when a
  plan limit is exceeded (e.g. the monthly post quota) as well as for
  insufficient permissions; previously both surfaced as the generic
  `SocialBuException`, forcing consumers to match on the message string. It
  extends `SocialBuException`, so existing catch blocks keep working.

### Fixed

- A media-upload response carrying a non-string `upload_token` now throws
  `MediaUploadException` like other malformed upload responses, instead of
  escaping as a raw `TypeError`.

## [1.5.0] - 2026-07-31

*This release was previously listed in this file as `[2.0.0]`, but it shipped
and was tagged as `v1.5.0`. Dropping Laravel 11 is a breaking change in strict
semver terms and rode in a minor release; recorded here as it happened.*

### Added

- Support for Laravel 13.x
- Support for PHP 8.5
- CI matrix now covers PHP 8.2-8.5 against Laravel 12.x and 13.x
- `MediaResource::uploadByUrl()` wrapping `POST /upload_media_by_url`. SocialBu
  fetches the file itself and returns an upload token in a single request,
  replacing the three-step flow's five requests, the download through your
  application, and the temp file. Requires a publicly reachable URL; `upload()`
  remains the option for URLs only your network can see. Failures carry the new
  `MediaUploadException::STEP_URL_UPLOAD` step.
- `PostResource::publishNow()` wrapping `POST /posts/{postId}/publish`, to
  release an existing scheduled or draft post immediately. Named to stay
  distinct from `SocialBu::publish()`, which creates a new post.
- `PostResource::bulkDelete()` wrapping `POST /posts/bulk_delete`, deleting many
  posts in one request instead of one per post.
- `PostResource::supportedOptions()` wrapping `GET /posts/supported-options`,
  returning `AccountSupportedOptions` keyed by account ID. Each exposes typed
  `PostOption` definitions -- label, type, storage, required, default, max
  length, and dropdown choices -- plus `validate()` to check an `options`
  payload against them before sending a post the platform would reject.
- `FakeSocialBu` support for the above: `withSupportedOptions()`,
  `assertPublishedNow()`, `assertDeleted()`, and `assertDeletedCount()`.
  `assertDeleted()` covers both `delete()` and `bulkDelete()`.

### Changed

- **BREAKING:** Dropped support for Laravel 11.x. The entire 11.x line is
  affected by an unpatched security advisory (CRLF injection in the default
  email rule, `>=11.0.0,<12.0.0`), and Laravel 11 is past its security-support
  window, so Composer refuses to install it under the default advisory policy.
  Upgrade to Laravel 12 or 13.
- **BREAKING:** Dropped `orchestra/testbench` 9.x from dev requirements; now
  requires `^10.0|^11.0`.
- Minimum Laravel is now 12.x. PHP 8.2 resolves to Laravel 12, since Laravel 13
  requires PHP 8.3+.

### Fixed

- `RateLimitException::retryAfter()` now returns `null` when the API omits the
  `Retry-After` header, instead of `0`. `Illuminate\Http\Client\Response::header()`
  returns an empty string rather than `null` for absent headers, so the previous
  null check never matched and callers doing `sleep($e->retryAfter() ?? 60)`
  would spin in a hot retry loop. A non-numeric `Retry-After` (an HTTP-date, which
  RFC 7231 permits) now also returns `null` rather than `0`.
- Post and account listing now send the page size as `perPage`, the spelling the
  API documents, matching the camelCase pagination fields it returns. The
  previous `per_page` was a guess -- no page-size parameter was documented at the
  time -- so `perPage` values were likely ignored and listings silently fell back
  to the server default. `per_page` is still sent alongside it pending
  confirmation against the live API.
- Remote media uploads no longer send an empty `Content-Type`. The fallback to
  `application/octet-stream` was written with `??`, which never applied because
  the header accessor returns an empty string. Content-Type parameters such as
  `; charset=binary` are now stripped, so `image/jpeg; charset=binary` is sent as
  `image/jpeg`.

## [1.4.1] - 2026-02-06

### Fixed

- `publish_at` parameter handling when creating posts.

## [1.4.0] - 2026-02-06

### Added

- `InsightsResource` covering the insights endpoints (post counts, post
  metrics, top posts, account metrics, followers and growth, engagement, team
  metrics and activity), with typed data classes under `Data/Insights`.

## [1.3.0] - 2026-02-06

### Added

- Pre-flight validation: account capability fields (`post_maxlength`,
  `max_attachments`, `attachment_types`, `post_media_required`),
  account-aware validation in `PostBuilder::send()`, and `toAccounts()` for
  zero-HTTP-call validation with pre-fetched accounts.

## [1.2.0] - 2026-02-06

### Fixed

- Structure of the data objects returned from the API wrapper.

## [1.0.0] - 2026-02-05

### Added

- Initial release
- Posts resource with CRUD operations and pagination
- Accounts resource with listing and pagination
- Media resource with 3-step S3 signed URL upload flow
- Fluent PostBuilder for creating posts
- Immutable DTOs: Post, Account, MediaUpload, PaginatedResponse
- Exception hierarchy with context for debugging
- Webhook support for post and account status callbacks
- Events: PostStatusChanged, AccountStatusChanged
- Artisan commands: socialbu:accounts, socialbu:test, socialbu:post
- FakeSocialBu testing helper for consuming applications
- Support for Laravel 11.x and 12.x
