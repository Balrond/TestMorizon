defmodule PhoenixApiWeb.ImportController do
  use PhoenixApiWeb, :controller
  alias PhoenixApi.Accounts

  @default_count 100

  def create(conn, params) do
    expected = System.fetch_env!("IMPORT_API_TOKEN")
    provided = conn |> get_req_header("x-api-token") |> List.first()

    if is_nil(provided) or byte_size(provided) != byte_size(expected) or
         not Plug.Crypto.secure_compare(provided, expected) do
      conn
      |> put_status(:unauthorized)
      |> json(%{error: "unauthorized"})
    else
      dry_run = Map.get(params, "dry_run") in [true, "1", "true", "yes"]

      count = parse_count(Map.get(params, "count"), @default_count)

      if dry_run do
        users = Accounts.preview_import_from_csv_sources(count)
        json(conn, %{dry_run: true, requested: count, count: length(users), users: users})
      else
        case Accounts.import_from_csv_sources(count) do
          {:ok, %{requested: req, inserted: ins, skipped: sk}} ->
            json(conn, %{requested: req, imported: ins, skipped: sk})

          {:error, reason} ->
            conn
            |> put_status(:unprocessable_entity)
            |> json(%{error: "import_failed", reason: inspect(reason)})
        end
      end
    end
  end

  defp parse_count(nil, default), do: default

  defp parse_count(v, _default) when is_integer(v) and v > 0, do: v

  defp parse_count(v, default) when is_binary(v) do
    case Integer.parse(v) do
      {n, _} when n > 0 -> n
      _ -> default
    end
  end

  defp parse_count(_v, default), do: default
end
