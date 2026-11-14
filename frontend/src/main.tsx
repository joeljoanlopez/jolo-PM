import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { App } from "./ui/App";
import { AuthGate } from "./ui/auth/AuthGate";
import { GatewayProvider } from "./ui/gateway";
import { HttpClient } from "./infrastructure/HttpClient";
import { HttpAuthGateway } from "./infrastructure/HttpAuthGateway";
import { HttpProjectsGateway } from "./infrastructure/HttpProjectsGateway";
import { getLanguage } from "./i18n/language";

// Raíz de composición del frontend: aquí se eligen los adaptadores de los puertos.
const http = new HttpClient("/api", getLanguage);

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <GatewayProvider gateway={new HttpProjectsGateway(http)} auth={new HttpAuthGateway(http)}>
      <AuthGate>
        <App />
      </AuthGate>
    </GatewayProvider>
  </StrictMode>,
);
