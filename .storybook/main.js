import path from 'path';

/** @type {import('@storybook/server-webpack5').StorybookConfig} */
const config = {
  stories: [
    '../public/packages/inno_frontend/components/**/*.stories.json',
  ],
  addons: [
    '@storybook/addon-essentials',
    '@storybook/addon-links',
    '@storybook/addon-a11y',
  ],
  framework: {
    name: '@storybook/server-webpack5',
    options: {},
  },
  staticDirs: [
    { from: '../public/packages/inno_frontend', to: '/packages/inno_frontend' },
  ],
  webpackFinal: async (config) => {
    config.resolve = config.resolve || {};
    config.resolve.alias = {
      ...config.resolve.alias,
      '~@helpers': path.resolve(__dirname, '../resources/css/helper'),
    };
    return config;
  },
};

export default config;
