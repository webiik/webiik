const webpack = require('webpack');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const {StatsWriterPlugin} = require("webpack-stats-plugin");
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
let configs = [];

// Entries
const entries = {
    // The following JS will be loaded only on client
    'client': {
        // App wide JS
        // _app.js is loaded in every route via getJS in _app.twig
        '_app': [
            './js/_app.ts',
        ],
        // Route specific JS
        // Webpack key MUST be the same as the route name in route configuration file
        // {key}.js is loaded in route via getJS in _app.twig
        'home': [
            './js/home.ts',
        ],
    },
    // The following JS will be loaded on server and client
    // This feature is intended to pre-render UI components
    'iso': {
        // Route specific JS
        // Webpack key MUST be the same as the route name in route configuration file and MUST include '-iso' suffix
        'home-iso': [
           './js/home-iso.ts',
        ],
    },
};


// Webpack configuration for client
const client = (env, argv) => {
    return {
        entry: Object.assign(entries.client, entries.iso),
        output: {
            filename: 'js/[name].js',
            chunkFilename: 'js/[name].js',
            path: path.resolve(__dirname + '/../../../public/assets/')
        },
        resolve: {
            extensions: ['.ts', '.tsx', '.js'],
        },
        optimization: {
            // Separate Webpack runtime to standalone file runtime.js
            runtimeChunk: {
                name: 'runtime'
            },
            // Split all shared code to chunk files vendors~{key}.js
            splitChunks: {
                chunks: 'all',
            }
        },
        plugins: [
            new webpack.DefinePlugin({
                // Define global variable(s) accessible in your JS code
                'WEBIIK_DEBUG': argv.mode === 'development',
            }),
            new MiniCssExtractPlugin({
                filename: 'css/[name].css',
            }),
            new CopyPlugin([
                {from: 'img', to: 'img'}
            ]),
            // Generate helper file assets.json
            // Thanks to this file Webiik knows which *.js files must be loaded in specific route
            new StatsWriterPlugin({
                filename: '../../private/frontend/assets/build/assets.json',
                stats: {
                    all: false,
                    entrypoints: true
                },
                transform(data) {
                    let resJson = {};
                    let addedAssets = {};
                    for (const entrypoint in data.entrypoints) {
                        let assetsJs = [];
                        let assetsCss = [];
                        const baseEntryPoint = entrypoint.replace(new RegExp('-iso$'), '');
                        for (const index in data.entrypoints[entrypoint].assets) {
                            if (!addedAssets[baseEntryPoint]) {
                                addedAssets[baseEntryPoint] = {};
                            }
                            if (!addedAssets[baseEntryPoint][data.entrypoints[entrypoint].assets[index]]) {
                                let asset = data.entrypoints[entrypoint].assets[index];
                                if (asset.match(/\.css$/)) {
                                    assetsCss.push(asset);
                                } else {
                                    if (data.entrypoints[entrypoint].assets[index] === 'js/runtime.js' && baseEntryPoint !== '_app') {
                                        // Don't push runtime.js to different entry point than _app
                                    } else {
                                        assetsJs.push(asset);
                                    }
                                }
                                addedAssets[baseEntryPoint][asset] = true;
                            }
                        }

                        resJson[entrypoint] = {};
                        resJson[entrypoint]['css'] = assetsCss;
                        resJson[entrypoint]['js'] = assetsJs;
                    }
                    return JSON.stringify(resJson, null, 2);
                }
            }),
            new CleanWebpackPlugin({
                dry: false,
                cleanStaleWebpackAssets: false,
            }),
        ],
        module: {
            rules: [
                {
                    test: /\.(scss|sass|css)$/,
                    exclude: /node_modules/,
                    use: [
                        {loader: MiniCssExtractPlugin.loader, options: {publicPath: '../'}},
                        'css-loader',
                        {loader: 'postcss-loader', options: {sourceMap: true}},
                        'resolve-url-loader',
                        {loader: 'sass-loader', options: {sourceMap: true}},
                    ]
                },
                {
                    test: /\.tsx?$/,
                    exclude: /node_modules/,
                    use: 'ts-loader',
                },
                {
                    test: /\.(eot|svg|ttf|woff2?|otf)$/,
                    exclude: /node_modules/,
                    options: {
                        name: '[path][name].[ext]',
                        // outputPath: './',
                    },
                    loader: 'file-loader',
                },
                {
                    test: /\.(ico|png|gif|jpg|jpeg)$/,
                    exclude: /node_modules/,
                    options: {
                        name: '[path][name].[ext]',
                        // outputPath: './',
                    },
                    loader: 'file-loader',
                }
            ]
        },
        externals: {
            // If you need to pass external data to your *.ts files, you can
            // do it via variable webpackData. Just define this variable before
            // calling getJS function in your (Twig) template and import it in
            // your *.ts file using `import webpackData from 'webpackData';`
            webpackData: 'webpackData'
        },
    }
};

// Webpack configuration for server (PHP v8js or NodeJS)
const server = (env, argv) => {
    return {
        entry: entries.iso,
        output: {
            filename: 'server/[name].js',
            chunkFilename: 'server/[name].js',
            path: path.resolve(__dirname + '/build/')
        },
        resolve: {
            extensions: ['.ts', '.tsx', '.js'],
        },
        plugins: [
            new webpack.DefinePlugin({
                // Define global variable(s) accessible in your JS code
                'WEBIIK_DEBUG': argv.mode === 'development',
            }),
            new CleanWebpackPlugin({
                dry: false,
                cleanStaleWebpackAssets: false,
            }),
        ],
        module: {
            rules: [
                {
                    test: /\.(scss|sass|css)$/,
                    exclude: /node_modules/,
                    use: [
                        // Translates CSS into CommonJS
                        'css-loader',
                        // Compiles Sass to CSS
                        'sass-loader',
                    ]
                },
                {
                    test: /\.tsx?$/,
                    exclude: /node_modules/,
                    use: 'ts-loader',
                },
                {
                    test: /\.(eot|svg|ttf|woff2?|otf)$/,
                    exclude: /node_modules/,
                    options: {
                        emitFile: false,
                    },
                    loader: 'file-loader',
                },
                {
                    test: /\.(ico|png|gif|jpg|jpeg)$/,
                    exclude: /node_modules/,
                    options: {
                        emitFile: false,
                    },
                    loader: 'file-loader',
                }
            ]
        },
        externals: {
            // If you need to pass external data to your *.ts files, you can
            // do it via variable webpackData. Just define this variable before
            // calling getJS function in your (Twig) template and import it in
            // your *.ts file using `import webpackData from 'webpackData';`
            webpackData: 'webpackData'
        },
    }
};

if (Object.keys(entries.client).length) configs.push(client);
if (Object.keys(entries.iso).length) configs.push(server);
module.exports = configs;