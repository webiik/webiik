const webpack = require('webpack');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
    return {
        entry: {
            main: ['./main.ts', './main.scss'],
            home: ['./js/home.ts', './scss/home.scss'],
        },
        output: {
            filename: '[name].js',
            chunkFilename: '[name].js',
            path: path.resolve(__dirname + '/../../../../public/assets/app/', 'js')
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
            })
        ],
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        'css-loader',
                        'postcss-loader',
                        'sass-loader'
                    ]
                }
                ,
                {
                    test: /\.tsx?$/,
                    use: 'ts-loader',
                    exclude: /node_modules/,
                }
            ]
        }
    }
};