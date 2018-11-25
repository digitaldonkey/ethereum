# Compile JS

to work on the javascrip youy need [browserify](http://browserify.org) or [watchify](https://www.npmjs.com/package/watchify).

Install watchify

```
npm install -g watchify
```

Start developing code with live compiling

```
cd ethereum/ethereum_signup/js
npm install
watchify ethereum-signup.js -o ethereum-signup.bundle.js
```
