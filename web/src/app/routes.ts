import { createBrowserRouter } from "react-router";
import { Layout } from "./components/Layout";
import { AuthLayout } from "./components/AuthLayout";
import { InvestorLayout } from "./components/InvestorLayout";
import { Home } from "./pages/Home";
import { Login } from "./pages/Login";
import { Register } from "./pages/Register";
import { ForgotPassword } from "./pages/ForgotPassword";
import { Dashboard } from "./pages/Dashboard";
import { Portfolios } from "./pages/Portfolios";
import { CreatePortfolio } from "./pages/CreatePortfolio";
import { PortfolioCreated } from "./pages/PortfolioCreated";
import { PortfolioDetail } from "./pages/PortfolioDetail";
import { RiskAnalysis } from "./pages/RiskAnalysis";
import { Transactions } from "./pages/Transactions";
import { CreateTransaction } from "./pages/CreateTransaction";
import { TransactionRegistered } from "./pages/TransactionRegistered";
import { Alerts } from "./pages/Alerts";
import { CreateAlert } from "./pages/CreateAlert";
import { AlertCreated } from "./pages/AlertCreated";
import { Profile } from "./pages/Profile";
import { Admin } from "./pages/Admin";
import { CreateUser } from "./pages/CreateUser";
import { UserCreated } from "./pages/UserCreated";
import { InvestorDashboard } from "./pages/InvestorDashboard";
import { InvestorPortfolio } from "./pages/InvestorPortfolio";
import { InvestorTransactions } from "./pages/InvestorTransactions";
import { InvestorAlerts } from "./pages/InvestorAlerts";
import { InvestorProfile } from "./pages/InvestorProfile";
import { About } from "./pages/About";

// Router configuration for MU Capital Platform
// Updated: Role selection removed, automatic role detection in Login
export const router = createBrowserRouter([
  {
    path: "/",
    Component: Layout,
    children: [
      { index: true, Component: Home },
      { path: "about", Component: About },
    ],
  },
  {
    path: "/auth",
    Component: AuthLayout,
    children: [
      { path: "login", Component: Login },
      { path: "register", Component: Register },
      { path: "forgot-password", Component: ForgotPassword },
    ],
  },
  {
    path: "/app",
    Component: Layout,
    children: [
      { path: "dashboard", Component: Dashboard },
      { path: "portfolios", Component: Portfolios },
      { path: "portfolios/create", Component: CreatePortfolio },
      { path: "portfolio-created", Component: PortfolioCreated },
      { path: "portfolios/:id", Component: PortfolioDetail },
      { path: "risk-analysis", Component: RiskAnalysis },
      { path: "transactions", Component: Transactions },
      { path: "transactions/create", Component: CreateTransaction },
      { path: "transaction-registered", Component: TransactionRegistered },
      { path: "alerts", Component: Alerts },
      { path: "alerts/create", Component: CreateAlert },
      { path: "alert-created", Component: AlertCreated },
      { path: "profile", Component: Profile },
      { path: "admin", Component: Admin },
      { path: "admin/users/create", Component: CreateUser },
      { path: "user-created", Component: UserCreated },
    ],
  },
  {
    path: "/investor",
    Component: InvestorLayout,
    children: [
      { path: "dashboard", Component: InvestorDashboard },
      { path: "portfolio", Component: InvestorPortfolio },
      { path: "transactions", Component: InvestorTransactions },
      { path: "alerts", Component: InvestorAlerts },
      { path: "profile", Component: InvestorProfile },
    ],
  },
]);