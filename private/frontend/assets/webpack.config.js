const webpack = require('webpack');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const RemovePlugin = require('remove-files-webpack-plugin');
const {StatsWriterPlugin} = require('webpack-stats-plugin');
let configs = [];

// Specify your Webpack entries here...
const entries = {
    /*
     * Specification of entry names:
     * 1) Entry names starting with a single underscore are automatically loaded in every route.
     * 2) Entry names with the same name as the route name are automatically loaded only within a specific route.
     * 3) Entry names with a name other than 1) 2) aren't automatically loaded.
     */
    'client': {
        /*
         * The following entries will be transpiled for the client and loaded
         * via getJS function in _app.twig after the main content block.
         * If you need to load an entry before the main content block,
         * just add a single underscore at the end of the entry name eg. `home_`.
         *
         * Transpiled files are located in `/public/assets`
         */
        '_app': [
            './js/_app.ts',
        ],
        'home': [
            './js/home.ts',
        ],
    },
    'iso': {
        /*
         * The following entries will transpiled for the client and for the server.
         * Client entries will be loaded via getJS function in _app.twig
         * before the main content block. Server entries can be loaded on
         * your demand, for example using the webiik/ssr package.
         *
         * Transpiled files are located in `/public/assets` and `/private/frontend/assets/build/server`
         *
         * !!! NOTICE !!!
         * All entry names defined in `iso` block MUST include '-iso' suffix
         */
        'home-iso': [
           './js/home-iso.ts',
        ],
    },
};

// Webpack configuration for the client
const client = (env, argv) => {
    return {
        entry: Object.assign(entries.client, entries.iso),
        output: {
            filename: 'js/[name].[contenthash].js',
            chunkFilename: 'js/[name].[contenthash].js',
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
                cacheGroups: {
                    defaultVendors: {
                        filename: 'js/vendors~[name].[contenthash].js',
                    },
                },
            },
        },
        plugins: [
            new webpack.DefinePlugin({
                // Define global variable(s) accessible in your JS code
                'WEBIIK_DEBUG': argv.mode === 'development',
            }),
            new MiniCssExtractPlugin({
                filename: 'css/[name].[contenthash].css',
            }),
            new CopyPlugin({
                patterns: [
                    {from: 'img', to: 'img'},
                ],
            }),
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
                            if (!addedAssets[baseEntryPoint][data.entrypoints[entrypoint].assets[index].name]) {
                                let asset = data.entrypoints[entrypoint].assets[index].name;
                                if (asset.match(/\.css$/)) {
                                    assetsCss.push(asset);
                                } else {
                                    if (data.entrypoints[entrypoint].assets[index].name.match(/js\/runtime\.\w+\.js/) && baseEntryPoint !== '_app') {
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
            new RemovePlugin({
                // Always remove those assets before build
                before: {
                    include: [
                        'build/server',
                        '../../../public/assets/img',
                        '../../../public/assets/css',
                        '../../../public/assets/js',
                    ],
                    test: [
                        {
                            folder: 'build',
                            method: (absPath) => new RegExp(/assets\.json$/).test(absPath)
                        }
                    ],
                    allowRootAndOutside: true
                },
                // Remove *.LICENSE.txt files generated by TerserPlugin after build
                after: {
                    test: [
                        {
                            folder: '../../../public/assets/js',
                            method: (absPath) => new RegExp(/\.LICENSE\.txt$/).test(absPath)
                        }
                    ],
                    allowRootAndOutside: true
                }
            })
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
                    },
                    loader: 'file-loader',
                },
                {
                    test: /\.(ico|png|gif|jpg|jpeg)$/,
                    exclude: /node_modules/,
                    options: {
                        name: '[path][name].[ext]',
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

// Webpack configuration for the server (PHP V8JS or NodeJS)
const server = (env, argv) => {
    return {
        entry: entries.iso,
        output: {
            filename: 'server/[name].[contenthash].js',
            chunkFilename: 'server/[name].[contenthash].js',
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
            new RemovePlugin({
                // Remove *.LICENSE.txt files generated by TerserPlugin during build
                after: {
                    test: [
                        {
                            folder: 'build/server',
                            method: (absPath) => new RegExp(/\.LICENSE\.txt$/).test(absPath)
                        }
                    ],
                    allowRootAndOutside: true
                }
            })
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

// Webpack configuration for old browsers
const polyfill = (env, argv) => {
    return {
        entry: {
            'polyfills': ['./js/polyfills.ts']
        },
        output: {
            filename: 'js/polyfills.js',
            path: path.resolve(__dirname + '/../../../public/assets/')
        },
        resolve: {
            extensions: ['.ts', '.tsx', '.js'],
        },
        plugins: [
            new webpack.DefinePlugin({
                // Define global variable(s) accessible in your JS code
                'WEBIIK_DEBUG': argv.mode === 'development',
            }),
        ],
        module: {
            rules: [
                {
                    test: /\.tsx?$/,
                    exclude: /node_modules/,
                    use: 'ts-loader',
                },
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
configs.push(polyfill);
module.exports = configs;