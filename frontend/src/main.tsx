import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { App } from "./ui/App";
import { GatewayProvider } from "./ui/gateway";
import { AuthProvider } from "./ui/auth/AuthProvider";
import { HttpClient } from "./infrastructure/HttpClient";
import { HttpProjectsGateway } from "./infrastructure/HttpProjectsGateway";
import { HttpAuthGateway } from "./infrastructure/HttpAuthGateway";
import { getLanguage } from "./i18n/language";

// Raíz de composición del frontend: aquí se eligen los adaptadores de los puertos.
const http = new HttpClient("/api", getLanguage);

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <AuthProvider gateway={new HttpAuthGateway(http)}>
      <GatewayProvider gateway={new HttpProjectsGateway(http)}>
        <App />
      </GatewayProvider>
    </AuthProvider>
  </StrictMode>,
);
