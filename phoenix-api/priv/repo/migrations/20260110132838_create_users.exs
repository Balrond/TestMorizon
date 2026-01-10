defmodule PhoenixApi.Repo.Migrations.CreateUsers do
  use Ecto.Migration

  def change do
    create table(:users) do
      add :external_id, :uuid, null: false
      add :first_name, :string, null: false
      add :last_name, :string, null: false
      add :birthdate, :date, null: false
      add :gender, :string, null: false

      timestamps(type: :utc_datetime)
    end

    create unique_index(:users, [:external_id])

    create unique_index(
             :users,
             [:first_name, :last_name, :birthdate, :gender],
             name: :users_unique_identity_idx
           )

    create index(:users, [:first_name])
    create index(:users, [:last_name])
    create index(:users, [:gender])
    create index(:users, [:birthdate])
  end
end
