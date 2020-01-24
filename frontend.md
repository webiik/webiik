---
layout: default
title: Frontend
permalink: /frontend/
---
# Frontend

If you need, you can use advanced frontend environment shipped with Webiik. It uses three core technologies: [Webpack](https://webpack.js.org), [SASS](https://sass-lang.com) and [TypeScript](https://www.typescriptlang.org). It compiles and minimizes TypeScript to ES5 and SCSS to CSS.  

## Requirements
* [Node & Npm](https://nodejs.org/en/) 

## How To Steps
1. In terminal open your project folder `private/app/frontend/assets`.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Build your files:
   ```bash
   npm run watch
   ```
   ⚠️ For **production** use `npm run prod`. If you don't want to automatically update your build files during development then use `npm run dev`.
   
See [directory structure](/directory-structure) to learn more about files in folder `private/app/frontend/assets`.
   
 