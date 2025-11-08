<script setup lang="ts">
import { computed, ref } from 'vue';
import { isCvs } from '~shopgo-ecpay/enum';
import { CvsStoreReturn, EcpayShippingParams, Shipping } from '~shopgo-ecpay/types';

const props = defineProps<{
  uid: string;
  shipping: Shipping<EcpayShippingParams>;
  mapRoute: string;
  callback: string;
}>();

const gateway = computed(() => props.shipping.params.gateway);
const isCvsType = computed(() => isCvs(gateway.value));
const data = ref<CvsStoreReturn>();

function mapSelect() {
  window.open(props.mapRoute, '', `width=900, height=600, top=300, left=300`);

  // @ts-ignore
  window[props.callback] = function(storeData: CvsStoreReturn) {
    data.value = storeData;
  };
}

</script>

<template>
<div v-if="isCvsType">
  <div :id="`shipping-form-${uid}`" class="l-shipping-form d-flex gap-3 align-items-center">
    <div class="l-shipping-form__button">
      <button type="button" class="btn btn-outline-primary" style="width: 135px"
        @click="mapSelect">
        選擇門市
      </button>
    </div>

    <div class="l-shipping-form__info">
      <template v-if="data">
        <div class="d-flex gap-2 align-items-center">
          <h6 class="m-0">{{ data.CVSStoreName }}</h6>
          <p class="text-muted m-0">{{ data.CVSAddress }}</p>

          <input name="checkout[shipping][CVSAddress]" type="hidden" :value="data.CVSAddress" />
          <input name="checkout[shipping][CVSOutSide]" type="hidden" :value="data.CVSOutSide" />
          <input name="checkout[shipping][CVSStoreID]" type="hidden" :value="data.CVSStoreID" />
          <input name="checkout[shipping][CVSStoreName]" type="hidden" :value="data.CVSStoreName" />
          <input name="checkout[shipping][CVSTelephone]" type="hidden" :value="data.CVSTelephone" />
          <input name="checkout[shipping][LogisticsSubType]" type="hidden" :value="data.LogisticsSubType" />
        </div>
      </template>
      <template v-else>
        <span class="text-danger">請選擇送貨門市</span>
        <input type="text" value="" class="d-none" required data-validation-message="請先選擇送貨門市" />
      </template>
    </div>
  </div>
</div>
</template>

<style scoped>

</style>
