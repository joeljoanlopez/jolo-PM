import { createContext, useContext, type ReactNode } from "react";
import type { ProjectsGateway } from "../ports/ProjectsGateway";

const GatewayContext = createContext<ProjectsGateway | null>(null);

export function GatewayProvider({ gateway, children }: { gateway: ProjectsGateway; children: ReactNode }) {
  return <GatewayContext.Provider value={gateway}>{children}</GatewayContext.Provider>;
}

export function useGateway(): ProjectsGateway {
  const gateway = useContext(GatewayContext);
  if (!gateway) throw new Error("Falta <GatewayProvider>");
  return gateway;
}
