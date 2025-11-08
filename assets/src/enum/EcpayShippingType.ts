export enum EcpayShippingType {
  TCAT = 'TCAT',         // 黑貓
  POST = 'POST',         // 中華郵政
  UNIMART = 'UNIMART',   // 統一超商
  FAMI = 'FAMI',         // 全家超商
  HILIFE = 'HILIFE',     // 萊爾富超商
  OKMART = 'OKMART'      // OK超商
}

export function isCvs(type: EcpayShippingType): boolean {
  return [
    EcpayShippingType.UNIMART,
    EcpayShippingType.FAMI,
    EcpayShippingType.HILIFE,
    EcpayShippingType.OKMART
  ].includes(type);
}
