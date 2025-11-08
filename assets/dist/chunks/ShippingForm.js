import { defineComponent as S, computed as u, ref as v, createElementBlock as p, createCommentVNode as _, openBlock as l, createElementVNode as n, createTextVNode as o, toDisplayString as m, Fragment as h } from "vue";
function f(s) {
  return [
    "UNIMART",
    "FAMI",
    "HILIFE",
    "OKMART"
    /* OKMART */
  ].includes(s);
}
const y = /* @__PURE__ */ S({
  __name: "ShippingForm",
  props: {
    uid: {},
    shipping: {},
    mapRoute: {},
    callback: {}
  },
  setup(s, { expose: e }) {
    e();
    const i = s, t = u(() => i.shipping.params.gateway), a = u(() => f(t.value)), d = v();
    function c() {
      window.open(i.mapRoute, "", "width=900, height=600, top=300, left=300"), window[i.callback] = function(g) {
        d.value = g;
      };
    }
    const r = { props: i, gateway: t, isCvsType: a, data: d, mapSelect: c };
    return Object.defineProperty(r, "__isScriptSetup", { enumerable: !1, value: !0 }), r;
  }
}), C = (s, e) => {
  const i = s.__vccOpts || s;
  for (const [t, a] of e)
    i[t] = a;
  return i;
}, k = { key: 0 }, V = ["id"], b = { class: "l-shipping-form__info" }, x = {
  key: 0,
  class: "d-flex gap-2 align-items-center"
}, T = { class: "m-0" }, w = { class: "text-muted m-0" }, N = ["value"], A = ["value"], F = ["value"], I = ["value"], O = ["value"], R = ["value"];
function D(s, e, i, t, a, d) {
  return t.isCvsType ? (l(), p("div", k, [
    n("div", {
      id: `shipping-form-${i.uid}`,
      class: "l-shipping-form d-flex gap-3 align-items-center"
    }, [
      n("div", { class: "l-shipping-form__button" }, [
        n("button", {
          type: "button",
          class: "btn btn-outline-primary",
          style: { width: "135px" },
          onClick: t.mapSelect
        }, `
        選擇門市
      `)
      ]),
      e[10] || (e[10] = o()),
      n("div", b, [
        t.data ? (l(), p("div", x, [
          n("h6", T, m(t.data.CVSStoreName), 1),
          e[0] || (e[0] = o()),
          n("p", w, m(t.data.CVSAddress), 1),
          e[1] || (e[1] = o()),
          n("input", {
            name: "checkout[shipping][CVSAddress]",
            type: "hidden",
            value: t.data.CVSAddress
          }, null, 8, N),
          e[2] || (e[2] = o()),
          n("input", {
            name: "checkout[shipping][CVSOutSide]",
            type: "hidden",
            value: t.data.CVSOutSide
          }, null, 8, A),
          e[3] || (e[3] = o()),
          n("input", {
            name: "checkout[shipping][CVSStoreID]",
            type: "hidden",
            value: t.data.CVSStoreID
          }, null, 8, F),
          e[4] || (e[4] = o()),
          n("input", {
            name: "checkout[shipping][CVSStoreName]",
            type: "hidden",
            value: t.data.CVSStoreName
          }, null, 8, I),
          e[5] || (e[5] = o()),
          n("input", {
            name: "checkout[shipping][CVSTelephone]",
            type: "hidden",
            value: t.data.CVSTelephone
          }, null, 8, O),
          e[6] || (e[6] = o()),
          n("input", {
            name: "checkout[shipping][LogisticsSubType]",
            type: "hidden",
            value: t.data.LogisticsSubType
          }, null, 8, R)
        ])) : (l(), p(h, { key: 1 }, [
          e[7] || (e[7] = n("span", { class: "text-danger" }, "請選擇送貨門市", -1)),
          e[8] || (e[8] = o()),
          e[9] || (e[9] = n("input", {
            type: "text",
            value: "",
            class: "d-none",
            required: "",
            "data-validation-message": "請先選擇送貨門市"
          }, null, -1))
        ], 64))
      ])
    ], 8, V)
  ])) : _("", !0);
}
const L = /* @__PURE__ */ C(y, [["render", D], ["__file", "ShippingForm.vue"]]);
export {
  L as default
};
//# sourceMappingURL=ShippingForm.js.map
