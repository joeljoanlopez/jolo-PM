import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { App } from "./ui/App";
import { GatewayProvider } from "./ui/gateway";
import { HttpProjectsGateway } from "./infrastructure/HttpProjectsGateway";
import { getLanguage } from "./i18n/language";

// Raíz de composición del frontend: aquí se elige el adaptador del puerto.
const gateway = new HttpProjectsGateway("/api", getLanguage);

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <GatewayProvider gateway={gateway}>
      <App />
    </GatewayProvider>
  </StrictMode>,
);
