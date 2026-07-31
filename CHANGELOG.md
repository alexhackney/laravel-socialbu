# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-07-29

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

## [1.0.0] - 2025-02-05

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
