import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { App } from "./ui/App";
import { GatewayProvider } from "./ui/gateway";
import { HttpProjectsGateway } from "./infrastructure/HttpProjectsGateway";

// Raíz de composición del frontend: aquí se elige el adaptador del puerto.
const gateway = new HttpProjectsGateway("/api");

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <GatewayProvider gateway={gateway}>
      <App />
    </GatewayProvider>
  </StrictMode>,
);
