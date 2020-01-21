---
layout: default
title: Frontend
permalink: /frontend/
---
# Frontend

If you need, you can use advanced frontend environment shipped with Webiik. It uses three core technologies: Webpack, SASS and TypeScript. It compiles and minimizes TypeScript to ES5 and SCSS to CSS.  

## Requirements
* [Node & Npm](https://nodejs.org/en/) 

## Installation Steps
1. In terminal open your project folder `private/app/frontend/assets`.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Run Webpack
   ```bash
   npm run watch
   ```
   ⚠️ For **production** use `npm run prod`. If you don't want to automatically update your build files during development then use `npm run dev`.
   
See [directory structure](/directory-structure) to learn more about files in folder `private/app/frontend/assets`.
   
 