export interface CvsStoreReturn {
  task: 'mapReply';
  id: string | number;
  callback: string;
  MerchantID: string;
  MerchantTradeNo: string;
  LogisticsSubType: string;
  CVSStoreID: string;
  CVSStoreName: string;
  CVSAddress: string;
  CVSTelephone: string;
  CVSOutSide: string;
  ExtraData: string;
}
