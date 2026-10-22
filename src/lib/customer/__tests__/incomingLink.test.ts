/// <reference types="jest" />
import { systemPathFor } from '../incomingLink';

describe('systemPathFor (App Links)', () => {
  it.each([
    ['https://rasta.arisheh.ir/', '/salon/rasta'],
    ['https://rasta.arisheh.ir/?src=qr', '/salon/rasta'],
    ['https://rasta.arisheh.ir/specialists/12?src=qr', '/salon/rasta?specialist=12'],
    ['https://arisheh.ir/', '/'],
    ['https://arisheh.ir/app', '/'],
    ['mahru://salon/rasta', 'mahru://salon/rasta'],
    ['/salon/rasta', '/salon/rasta'],
  ])('%s → %s', (input, expected) => {
    expect(systemPathFor(input)).toBe(expected);
  });
});
