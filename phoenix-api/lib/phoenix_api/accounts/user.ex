defmodule PhoenixApi.Accounts.User do
  use Ecto.Schema
  import Ecto.Changeset

  schema "users" do
    field :external_id, Ecto.UUID
    field :first_name, :string
    field :last_name, :string
    field :gender, :string
    field :birthdate, :date

    timestamps()
  end

  @doc false
  def changeset(user, attrs) do
    user
    |> cast(attrs, [:external_id, :first_name, :last_name, :gender, :birthdate])
    |> maybe_put_external_id()
    |> validate_required([:external_id, :first_name, :last_name, :gender, :birthdate])
  end

  defp maybe_put_external_id(%Ecto.Changeset{} = cs) do
    if get_field(cs, :external_id) do
      cs
    else
      put_change(cs, :external_id, Ecto.UUID.generate())
    end
  end
end
