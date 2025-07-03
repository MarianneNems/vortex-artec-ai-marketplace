module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        targets: {
          browsers: ['> 1%', 'last 2 versions', 'not dead', 'ie >= 11'],
        },
        useBuiltIns: 'entry',
        corejs: 3,
        modules: false,
      },
    ],
  ],
  plugins: ['@babel/plugin-proposal-class-properties', '@babel/plugin-syntax-dynamic-import'],
  env: {
    test: {
      presets: [
        [
          '@babel/preset-env',
          {
            targets: {
              node: 'current',
            },
          },
        ],
      ],
      plugins: ['@babel/plugin-proposal-class-properties'],
    },
  },
};
