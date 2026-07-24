package main

import (
	"fmt"
	"os"

	"pinglab/ui/internal/config"
	uiapp "pinglab/ui/internal/ui"
)

// version is set at build time via -ldflags "-X main.version=...".
// See ui/Makefile, ui/build.sh, ui/build.ps1 and .github/workflows/ui-release.yml.
var version = "dev"

func main() {
	if len(os.Args) > 1 && (os.Args[1] == "-v" || os.Args[1] == "--version") {
		fmt.Println("eping " + version)
		return
	}

	cfg := config.Load()
	if err := uiapp.Run(cfg); err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}
}
