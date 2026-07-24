# Build helpers for the eping Go terminal client.
#
# Usage:
#   make build        Build a binary for the current OS/arch
#   make build-all     Cross-compile for Windows, Linux and macOS (amd64+arm64)
#   make test          Run the Go test suite
#   make vet           Run go vet
#   make clean         Remove build output

BINARY  := eping
DIST    := dist

# Version resolution order (see docs/BUILD.md#versioning):
#   1. Explicit `make VERSION=v0.2 ...` / VERSION env var.
#   2. The exact git tag pointing at HEAD (e.g. v0.1).
#   3. The ./VERSION file (next planned release) plus a dev/commit suffix.
#   4. "dev" if none of the above are available.
VERSION ?= $(shell \
	tag=$$(git describe --tags --exact-match 2>/dev/null); \
	if [ -n "$$tag" ]; then echo "$$tag"; \
	elif [ -f VERSION ]; then \
		base="v$$(tr -d '[:space:]' < VERSION)-dev"; \
		sha=$$(git rev-parse --short HEAD 2>/dev/null); \
		if [ -n "$$sha" ]; then echo "$${base}+$${sha}"; else echo "$$base"; fi; \
	else echo dev; fi \
)
LDFLAGS := -s -w -X main.version=$(VERSION)

PLATFORMS := windows/amd64 windows/arm64 linux/amd64 linux/arm64 darwin/amd64 darwin/arm64

.PHONY: build build-all test vet clean $(PLATFORMS)

build:
	go build -ldflags "$(LDFLAGS)" -o $(BINARY) .

test:
	go test ./...

vet:
	go vet ./...

build-all: $(PLATFORMS)

$(PLATFORMS):
	$(eval GOOS := $(word 1,$(subst /, ,$@)))
	$(eval GOARCH := $(word 2,$(subst /, ,$@)))
	$(eval EXT := $(if $(filter windows,$(GOOS)),.exe,))
	mkdir -p $(DIST)
	GOOS=$(GOOS) GOARCH=$(GOARCH) CGO_ENABLED=0 go build -ldflags "$(LDFLAGS)" -o $(DIST)/$(BINARY)-$(GOOS)-$(GOARCH)$(EXT) .
	@echo "built $(DIST)/$(BINARY)-$(GOOS)-$(GOARCH)$(EXT)"

clean:
	rm -rf $(DIST) $(BINARY) $(BINARY).exe
