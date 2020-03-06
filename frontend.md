---
layout: default
title: Frontend
permalink: /frontend/
---
# Frontend

If you need, you can use advanced frontend environment shipped with Webiik. It uses three core technologies: [Webpack](https://webpack.js.org), [SASS](https://sass-lang.com) and [TypeScript](https://www.typescriptlang.org). It builds all necessary frontend files from `private/app/frontend/assets/app` and place them to folder `public/assets/app`.   

## Requirements
* [Node & Npm](https://nodejs.org/en/) 

## How To Steps
1. In terminal open your project folder `private/app/frontend/assets/app`.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Build your files:
   ```bash
   npm run watch
   ```
   ⚠️ For **production** use `npm run prod`. If you don't want to automatically update your build files during development then use `npm run dev`.  

## Configuration
See standard NPM and Webpack configuration files in `private/app/frontend/assets/app` and adjust them by your needs. Webiik doesn't use any extra configuration for processing frontend files. If you need to configure template engine read [View](view.md) docs.
   
 