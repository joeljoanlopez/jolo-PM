import { createContext, useContext, type ReactNode } from "react";
import type { AuthGateway } from "../ports/AuthGateway";
import type { ProjectsGateway } from "../ports/ProjectsGateway";

const GatewayContext = createContext<ProjectsGateway | null>(null);
const AuthGatewayContext = createContext<AuthGateway | null>(null);

export function GatewayProvider({ gateway, auth, children }: { gateway: ProjectsGateway; auth: AuthGateway; children: ReactNode }) {
  return (
    <AuthGatewayContext.Provider value={auth}>
      <GatewayContext.Provider value={gateway}>{children}</GatewayContext.Provider>
    </AuthGatewayContext.Provider>
  );
}

export function useGateway(): ProjectsGateway {
  const gateway = useContext(GatewayContext);
  if (!gateway) throw new Error("Falta <GatewayProvider>");
  return gateway;
}

export function useAuthGateway(): AuthGateway {
  const auth = useContext(AuthGatewayContext);
  if (!auth) throw new Error("Falta <GatewayProvider>");
  return auth;
}
