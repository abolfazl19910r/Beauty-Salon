/// <reference types="jest" />
import { systemPathFor } from '../incomingLink';

describe('systemPathFor (App Links)', () => {
  it.each([
    ['https://rasta.mahru.ir/', '/salon/rasta'],
    ['https://rasta.mahru.ir/?src=qr', '/salon/rasta'],
    ['https://rasta.mahru.ir/specialists/12?src=qr', '/salon/rasta?specialist=12'],
    ['https://mahru.ir/', '/'],
    ['https://mahru.ir/app', '/'],
    ['mahru://salon/rasta', 'mahru://salon/rasta'],
    ['/salon/rasta', '/salon/rasta'],
  ])('%s → %s', (input, expected) => {
    expect(systemPathFor(input)).toBe(expected);
  });
});
