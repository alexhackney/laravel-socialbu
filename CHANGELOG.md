# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
