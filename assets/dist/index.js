import { useUnicorn as o } from "@windwalker-io/unicorn-next";
import { defineAsyncComponent as p } from "vue";
function e() {
  o().provide("shopgo.ecpay.shipping-form", p(() => import("./chunks/ShippingForm.js")));
}
export {
  e as useShopGoEcpay
};
//# sourceMappingURL=index.js.map
