//go:build !windows && !linux && !darwin

package linktype

func detect() Type {
	return Unknown
}
