---
layout: default
title: Frontend
permalink: /frontend/
---
# Frontend
Webiik comes with advanced frontend environment based on:
* Webpack
* NodeJS & NPM
* TypeScript
* SCSS

Out of the box features:
* Server-side rendering of React components (NodeJS or PHPV8JS)
* JS code minification and splitting (Webpack)
* CSS minification, auto-prefixing, and splitting (Webpack)
* TypeScript support (Webpack)
* SCSS support (Webpack)
* Twig templates with support for things mentioned above (Webiik)

## Directory structure
Frontend related files have the following structure: 
```console
.
.. (this folder must be not accessible from the web)
├── private (this folder must be not accessible from the web)
|   └── frontend (templates and assets)
|       ├── assets (read the frontend docs in the basics)
|       |   ├── build (js build for client and server)
|       |   ├── components (UI components)
|       |   |   └── meow (React Meow World! component)
|       |   |       ├── meow.scss
|       |   |       └── meow.tsx
|       |   ├── font (css font files)
|       |   ├── img (images)
|       |   ├── js (js or ts files)
|       |   |   ├── _app.ts (app-wide TypeScript file included in webpack.config.js)
|       |   |   ├── home.ts (route related TypeScript file included in webpack.config.js)
|       |   |   ├── home-iso.ts (route related TypeScript file included in webpack.config.js)
|       |   |   └── screensize.ts (helper for frontend development included in _app.ts)
|       |   ├── scss (scss files)
|       |   |   ├── _app.scss (app-wide scss file included in app.ts)
|       |   |   └── home.scss (route related scss file included in home.ts)
|       |   ├── global.d.ts (global helper variable definition)
|       |   ├── package.json (NPM file)
|       |   ├── postcss.config.js (Webpack's postcss-loader config)
|       |   ├── tsconfig.json (TypeScript config)
|       |   └── webpack.config.js (Webpack config)
|       ├── _app.twig (base Twig template)
|       └── home.twig (route related Twig template)
└── public (this folder must be accessible from the web)
    └── assets (static files processed by Webpack)
        ├── css
        ├── font
        ├── img
        └── js
``` 

## How To Proceed When Building Frontend
1. Open the terminal and go to folder: `private/frontend/assets`
2. Install dependencies required for frontend development: 
   ```bash
   npm install
   ```
   > Maybe you will need to install [npm](https://nodejs.org/en/).
3. In your editor, prepare Twig templates inside `private/frontend`:
    * Names of base templates SHOULD start with `_` eg. `_app.twig`
    * Names of route related templates SHOULD be named by [a route name](/routing) eg. `home.twig`
    * See additional Webiik-Twig functions [(see View)](/view)
    > This is standard Twig, no magic behind. If you don't know what you are doing, read [Twig docs](https://twig.symfony.com).
4. Place your assets: JS(TS) files, images, fonts, CSS(SCSS) files, and React components into appropriate folders (see the directory structure above).
    * Notice the `_app` files in `js` and `scss` folders. Webiik loads these files in every route.
    * Route related files SHOULD be named by route name.
    * You can insert external parameters to your JS(TS) files via `webpackData` variable.
    * You can use special `WEBIIK_DEBUG` variable inside your JS(TS) files. `WEBIIK_DEBUG` is `true` when code is build with `watch` or `dev` NPM script (see point 6).
5. In your editor, open `private/frontend/assets/webpack.config.js` and add your assets to the `entries` object. Even if you don't plan to use server side rendering, it's a good idea to import your React components separately in `iso` files. Please, read comments within `webpack.config.js` for more details.
    > This is standard Webpack, no magic behind. If you don't know what you are doing, read [Webpack docs](https://webpack.js.org/concepts/).   
6. Build your files:
   ```bash
   npm run dev
   ```
   * You should find processed files inside `public/assets` and `private/frontend/assets/build`.
   * ⚠️ For production-ready files use `npm run prod`.
   * If you want to automatically update your build files during development then use `npm run watch`.