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
VERSION ?= $(shell git describe --tags --always --dirty 2>/dev/null || echo dev)
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
