import { useUnicorn } from '@windwalker-io/unicorn-next';
import { defineAsyncComponent } from 'vue';

export function useShopGoEcpay() {
  const u = useUnicorn();

  u.provide('shopgo.ecpay.shipping-form', defineAsyncComponent(() => import('./components/ShippingForm.vue')));
}
