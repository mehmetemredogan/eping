# Contributing to ePing

Thanks for considering a contribution! This guide covers both parts of the
project: the Laravel web app (repository root) and the Go terminal client
(`ui/`).

## Getting started

1. Fork the repository and clone your fork.
2. Follow the setup steps in [`README.md`](README.md) / [`README.en.md`](README.en.md).
3. Create a feature branch off `main`:

   ```bash
   git checkout -b feature/short-description
   ```

## Web app (Laravel)

- Run the app locally with `composer run dev` (server + queue + logs + Vite).
- Run the test suite before submitting changes:

  ```bash
  composer run test
  ```

- Follow existing code style; the project uses [Laravel Pint](https://laravel.com/docs/pint):

  ```bash
  ./vendor/bin/pint
  ```

- Keep controllers thin — put business logic in `app/Services/*`.
- Any new user-facing string must be added to **both**
  `lang/tr/*.php` and `lang/en/*.php` (see [Language support](README.md#dil-desteği)
  in the README). Never hardcode Turkish or English text directly in a Blade
  view or controller.

## Terminal client (`ui/`)

- Run tests: `go test ./...` (from the `ui/` directory).
- Run `go vet ./...` and `go mod tidy` before committing; the CI pipeline
  (`.github/workflows/ui-ci.yml`) checks that `go.mod`/`go.sum` are tidy.
- Build for your platform with `go build .`, or cross-compile with
  `make build-all` / `./build.sh` / `./build.ps1` — see
  [`docs/BUILD.md`](docs/BUILD.md).
- Keep Ping and Tracert output visually and structurally separate — never
  merge them into a single line or field (see `ui/README.md` for the
  rationale).

## Commit messages

Use short, imperative commit messages that explain *why* a change was made,
e.g. `Fix packet loss calculation for timed-out pings` rather than
`Update PingService.php`.

## Pull requests

- Keep PRs focused on a single change; large unrelated changes are harder to
  review.
- Make sure CI is green: `ui-ci.yml` must pass for any change touching `ui/**`.
- Describe what changed and why in the PR description. Include a test plan
  (commands you ran, screenshots for UI changes) where relevant.

## Reporting bugs / requesting features

Please open a GitHub issue with:

- Steps to reproduce (for bugs) or the use case (for features).
- Expected vs. actual behavior.
- Environment details (OS, PHP/Go version) when relevant.

## Code of conduct

Be respectful and constructive. We want ePing to be a welcoming project for
contributors of all backgrounds and experience levels.
