export function getInstallPayload() {
  return (
    window.__INSTALL_BOOTSTRAP__ || {
      step: "welcome",
      strings: {},
      stepData: {},
    }
  );
}
