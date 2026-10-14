/// <reference types="jest" />
import { discountLabel, gatewayFee, walletShare } from '../money';

describe('gatewayFee (same formula as SalonPaymentGateway::feeRialFor)', () => {
  it('rounds the percentage up and adds the fixed fee', () => {
    expect(gatewayFee({ fee_percent: 1, fee_fixed: 0 }, 150_050)).toBe(1501);
    expect(gatewayFee({ fee_percent: 0.5, fee_fixed: 1000 }, 100_000)).toBe(1500);
    expect(gatewayFee({ fee_percent: 0, fee_fixed: 0 }, 100_000)).toBe(0);
  });

  it('charges nothing for nothing (fully paid from the wallet)', () => {
    expect(gatewayFee({ fee_percent: 1, fee_fixed: 500 }, 0)).toBe(0);
  });
});

describe('walletShare', () => {
  it('never exceeds the balance or the amount', () => {
    expect(walletShare(true, 30_000, 100_000)).toBe(30_000);
    expect(walletShare(true, 300_000, 100_000)).toBe(100_000);
    expect(walletShare(false, 300_000, 100_000)).toBe(0);
  });
});

it('discountLabel', () => {
  expect(discountLabel('percentage', 20)).toBe('۲۰٪ تخفیف');
  expect(discountLabel('fixed', 50000)).toBe('۵۰٬۰۰۰ تومان تخفیف');
});
