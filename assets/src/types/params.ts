import { EcpayShippingType } from '~shopgo-ecpay/enum';

export interface EcpayShippingParams {
  gateway: EcpayShippingType;
  hash_iv: string;
  cvs_type: 'B2C' | 'B2B';
  hash_key: string;
  total_gt: string;
  total_lt: string;
  goods_name: string;
  merchant_id: string;
  sender_name: string;
  temperature: string;
  cod_payments: any[];
  sender_phone: string;
  unpick_state: string;
  cvs_max_amount: number;
  cvs_min_amount: number;
  received_state: string;
  sender_address: string;
  sender_zipcode: string;
  shipping_state: string;
  delivered_state: string;
  orderinfo_layout: string;
  sender_cellphone: string;
  checkout_form_inject_id: string;
}
