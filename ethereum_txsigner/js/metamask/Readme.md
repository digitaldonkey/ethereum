**Reinitialize Mascara**

You might force to recreate Mascara by unregistering the Service Worker.

In Firefox `about:debugging#workers`
In Chrome dev tools go to Application>Service Workers


`npm install --global browserify`
`npm install --global watchify`

cd ethereum/ethereum_txsigner/js/metamask
browserify src/metamask.js -o built/bundle.js

watchify src/metamask.js -o built/bundle.js -v -t