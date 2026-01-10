defmodule PhoenixApiWeb.UserController do
  use PhoenixApiWeb, :controller

  alias PhoenixApi.Accounts

  action_fallback PhoenixApiWeb.FallbackController

  def index(conn, params) do
    users = Accounts.list_users(params)
    render(conn, :index, users: users)
  end

  def create(conn, %{"user" => user_params}) do
    with {:ok, user} <- Accounts.create_user(user_params) do
      conn
      |> put_status(:created)
      |> render(:show, user: user)
    end
  end

  def show(conn, %{"id" => id}) do
    case Accounts.get_user(id) do
      nil -> {:error, :not_found}
      user -> render(conn, :show, user: user)
    end
  end

  def update(conn, %{"id" => id, "user" => user_params}) do
    case Accounts.get_user(id) do
      nil ->
        {:error, :not_found}

      user ->
        with {:ok, user} <- Accounts.update_user(user, user_params) do
          render(conn, :show, user: user)
        end
    end
  end

  def delete(conn, %{"id" => id}) do
    case Accounts.get_user(id) do
      nil ->
        {:error, :not_found}

      user ->
        with {:ok, _user} <- Accounts.delete_user(user) do
          send_resp(conn, :no_content, "")
        end
    end
  end
end
