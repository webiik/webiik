const webpack = require('webpack');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyPlugin = require('copy-webpack-plugin');

module.exports = (env, argv) => {
    return {
        entry: {
            main: [
                './main.tsx',
                './main.scss'
            ],
            home: [
                './js/home.tsx',
                './scss/home.scss'
            ],
        },
        output: {
            filename: '[name].js',
            chunkFilename: '[name].js',
            path: path.resolve(__dirname + '/../../../../../public/assets/' + path.basename(path.resolve()) + '/', 'js')
        },
        resolve: {
            extensions: ['.tsx', '.ts', '.js'],
        },
        optimization: {
            // Remove duplicated dependencies
            splitChunks: {
                chunks: 'all'
            }
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: '../css/[name].css'
            }),
            new webpack.DefinePlugin({
                'WEBIIK_DEBUG': argv.mode === 'development'
            }),
            new CopyPlugin([
                { from: 'img', to: '../img' }
            ])
        ],
        module: {
            rules: [
                {
                    test: /\.(scss|sass|css)$/,
                    exclude: /node_modules/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        'css-loader',
                        {loader: 'postcss-loader', options: {sourceMap: true}},
                        'resolve-url-loader',
                        {loader: 'sass-loader', options: {sourceMap: true}}
                    ]
                },
                {
                    test: /\.tsx?$/,
                    exclude: /node_modules/,
                    use: 'ts-loader'
                },
                {
                    test: /\.(eot|svg|ttf|woff2?|otf)$/,
                    exclude: /node_modules/,
                    options: {
                        name: '[path][name].[ext]',
                        outputPath: '../',
                    },
                    loader: 'file-loader'
                },
                {
                    test: /\.(ico|png|gif|jpg|jpeg)$/,
                    exclude: /node_modules/,
                    options: {
                        name: '[path][name].[ext]',
                        outputPath: '../',
                    },
                    loader: 'file-loader'
                }
            ]
        }
    }
};