{
  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
    claude-code.url = "github:sadjow/claude-code-nix";
  };

  outputs =
    { nixpkgs, claude-code, ... }:
    let
      system = "x86_64-linux";
      pkgs = import nixpkgs { inherit system; };
    in
    {
      devShells.${system} = {
        default = pkgs.mkShell {
          packages = [
            claude-code.packages.${system}.default
            pkgs.nodejs_20
            pkgs.php83
            pkgs.semgrep
            pkgs.detect-secrets
            # Playwright browsers with all required system libraries
            pkgs.playwright-driver.browsers
          ];
          shellHook = ''
            export PLAYWRIGHT_BROWSERS_PATH=${pkgs.playwright-driver.browsers}
            export PLAYWRIGHT_SKIP_VALIDATE_HOST_REQUIREMENTS=true
            # Point playwright to the nix-provided chromium binary (version may differ from npm playwright)
            export PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH=${pkgs.playwright-driver.browsers}/chromium-1208/chrome-linux64/chrome
          '';
        };

        storybook = pkgs.mkShell {
          packages = [
            pkgs.php83
          ];
          shellHook = ''
            echo "PHP story server shell ready."
            echo "Run: php -S localhost:6007 storybook-server/index.php"
          '';
        };
      };
    };
}
