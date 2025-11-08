import vuePlugin from '@vitejs/plugin-vue';
import { resolve } from 'node:path';
import dts from 'unplugin-dts/vite';
import { defineConfig } from 'vite';
import vueComponentOverride from 'vite-plugin-vue-component-override/plugin';

export default defineConfig(() => {
  return {
    base: './',
    resolve: {
      alias: {
        '~shopgo-ecpay': resolve('./src'),
      },
    },
    build: {
      lib: {
        entry: './src/index.ts',
        name: 'ShopGoEcpay',
        formats: ['es'],
      },
      rollupOptions: {
        output: {
          format: 'es',
          entryFileNames: '[name].js',
          chunkFileNames: 'chunks/[name].js',
          assetFileNames: (info) => {
            // if (info.originalFileNames[0] === 'style.css') {
            //   return 'shopgo.css';
            // }

            return 'assets/[name][extname]';
          },
          // manualChunks(id, meta) {
          //   const file = id.split('?').shift();
          //   const src = resolve('./src');
          //
          //   if (file.endsWith('.vue') && file.startsWith(src)) {
          //     const relativePath = file.substring(src.length + 1);
          //
          //     return relativePath;
          //   }
          // }
        },
        external: [
          '@windwalker-io/unicorn-next',
          '@lyrasoft/ts-toolkit',
          /^@lyrasoft\/ts-toolkit/,
          '@unicorn/*',
          'sweetalert',
          'bootstrap',
          'sortablejs',
          /^swiper/,
          '@asika32764/vue-animate',
          'bootstrap',
          'vue',
          'vue-draggable-plus',
          'vue-multi-uploader',
          // /^vite-plugin-vue-component-override/,
        ]
      },
      outDir: 'dist',
      emptyOutDir: true,
      sourcemap: true,
      // sourcemap: 'inline',
      minify: true,
    },
    plugins: [
      vuePlugin({
        features: {
          prodDevtools: true,
        },
        template: {
          compilerOptions: {
            // preserveWhitespace: false,
            whitespace: 'preserve',
          },
        }
      }),

      dts({
        tsconfigPath: resolve('./tsconfig.json'),
        bundleTypes: true,
      }),

      // vueComponentOverride({})

//       {
//         name: 'vue-override',
//         enforce: 'pre',
//         transform(code, id) {
//           if (!/\.(js|ts|jsx|tsx|vue)$/.test(id)) {
//             return null
//           }
//
//           let helper = false;
//
//           code = code.replaceAll(/import\s+(.*?)\s+from\s+'((.*?)\.vue)'\s*(;?)/g, (match, component, uri) => {
//             if (component.includes('__Tmp')) {
//               return match;
//             }
//
//             const tmpName = component + '__Tmp' + Math.floor(Math.random() * 100000);
//             let replaced = `import ${tmpName} from '${uri}';\n
// const ${component} = useUnicorn().inject('${uri}') ?? ${tmpName};`;
//
//             if (!helper && !code.match(/\{.*?useUnicorn.*?\}\s+from/)) {
//               replaced = `import { useUnicorn } from '@windwalker-io/unicorn-next';\n` + replaced;
//               helper = true;
//             }
//
//             return replaced;
//           });
//
//           return {
//             code,
//             map: null
//           };
//         }
//       }
    ]
  };
});



