import { AccountBlogLink } from "./components/AccountBlogLink";
import apiFetch from "@wordpress/api-fetch";

const { render, useState } = wp.element;

const ManageConnections = () => {
  const [accounts, setAccounts] = useState([]);

  apiFetch({ path: "/smolblog/v1/accounts/blogs" }).then(setAccounts);

  return (
    <table className="widefat striped fixed">
      <thead>
        <tr>
          <th>Account</th>
          <th>Push</th>
          <th>Pull</th>
        </tr>
      </thead>

      <tbody>
        {accounts.map((account) => (
          <AccountBlogLink account={account} />
        ))}
      </tbody>
    </table>
  );
};

render(
  <ManageConnections />,
  document.getElementById("smolblog-social-connections-app")
);
