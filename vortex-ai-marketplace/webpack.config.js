const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const ESLintPlugin = require('eslint-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      // Main frontend scripts
      'vortex-marketplace': './public/js/vortex-marketplace.js',
      'vortex-tola': './public/js/vortex-tola.js',
      'quiz-optimizer': './public/js/quiz-optimizer.js',
      'quiz-enhanced': './public/js/quiz-enhanced.js',
      
      // Admin scripts
      'vortex-admin': './admin/js/vortex-admin.js',
      'thorius-admin': './admin/js/thorius-admin.js',
      
      // AI components
      'ai-terminal': './public/js/ai-terminal.js',
      'huraii-components': './public/js/huraii-components/index.js',
      
      // Blockchain components
      'blockchain-wallet': './public/js/blockchain/wallet-connect.js',
      'tola-integration': './public/js/blockchain/tola-integration.js'
    },

    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: 'js/[name].[contenthash].js',
      chunkFilename: 'js/[name].[contenthash].chunk.js',
      publicPath: './',
      clean: true
    },

    module: {
      rules: [
        // JavaScript/ES6+ processing
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env'],
              plugins: ['@babel/plugin-proposal-class-properties']
            }
          }
        },

        // CSS/SCSS processing
        {
          test: /\.(css|scss)$/,
          use: [
            isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
            'css-loader',
            {
              loader: 'sass-loader',
              options: {
                implementation: require('sass'),
                sassOptions: {
                  outputStyle: 'compressed'
                }
              }
            }
          ]
        },

        // Images
        {
          test: /\.(png|jpg|jpeg|gif|svg)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name].[hash][ext]'
          }
        },

        // Fonts
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name].[hash][ext]'
          }
        }
      ]
    },

    plugins: [
      new CleanWebpackPlugin(),

      // Extract CSS into separate files
      new MiniCssExtractPlugin({
        filename: 'css/[name].[contenthash].css',
        chunkFilename: 'css/[name].[contenthash].chunk.css'
      }),

      // Copy static assets
      new CopyWebpackPlugin({
        patterns: [
          {
            from: 'public/images',
            to: 'images',
            noErrorOnMissing: true
          },
          {
            from: 'public/fonts',
            to: 'fonts',
            noErrorOnMissing: true
          }
        ]
      }),

      // ESLint integration
      new ESLintPlugin({
        extensions: ['js'],
        exclude: ['node_modules', 'vendor']
      })
    ],

    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            compress: {
              drop_console: isProduction
            }
          }
        }),
        new CssMinimizerPlugin()
      ],

      splitChunks: {
        chunks: 'all',
        cacheGroups: {
          // Vendor libraries
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendors',
            chunks: 'all',
            priority: 10
          },
          
          // Common VORTEX utilities
          common: {
            name: 'common',
            minChunks: 2,
            chunks: 'all',
            priority: 5,
            enforce: true
          }
        }
      },

      runtimeChunk: {
        name: 'runtime'
      }
    },

    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'public/js'),
        '@admin': path.resolve(__dirname, 'admin/js'),
        '@components': path.resolve(__dirname, 'public/js/components'),
        '@utils': path.resolve(__dirname, 'public/js/utils')
      },
      extensions: ['.js', '.json']
    },

    devServer: {
      static: {
        directory: path.join(__dirname, 'dist')
      },
      compress: true,
      port: 3000,
      hot: true,
      open: false,
      proxy: {
        '/wp-admin': 'http://localhost',
        '/wp-json': 'http://localhost'
      }
    },

    devtool: isProduction ? 'source-map' : 'eval-source-map',

    performance: {
      hints: isProduction ? 'warning' : false,
      maxEntrypointSize: 512000,
      maxAssetSize: 512000
    },

    stats: {
      colors: true,
      modules: false,
      children: false,
      chunks: false,
      chunkModules: false
    }
  };
}; 