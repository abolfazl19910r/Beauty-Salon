/// <reference types="jest" />
/** expo-task-manager در Jest: task تعریف‌شده نگه داشته می‌شود تا آزمون آن را مثل اندروید صدا بزند (بسته‌ی ۴). */
export const tasks: Record<string, (body: { data: unknown; error: unknown }) => Promise<void>> = {};
export const defineTask = jest.fn((name: string, fn: (body: { data: unknown; error: unknown }) => Promise<void>) => {
  tasks[name] = fn;
});
