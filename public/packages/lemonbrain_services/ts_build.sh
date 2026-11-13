#!/usr/bin/env bash

bun build ./js/src/accept_cookies/accept_cookies.ts --outdir ./js/min/accept_cookies --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/unsupported_browsers/unsupported_browsers.ts --outdir ./js/min/unsupported_browsers --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/google.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/spa.util.uriAnchor.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/lb.util.uriParam.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/lb.util.uriParam.urlHandler.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/recaptchaV3-vanilla-js.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/spa.util.mailAddr.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/spa.shell.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/spa.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/htmlSanitizer.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/imageHelper.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext] &
