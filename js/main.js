import { AccountBlogLink } from "./components/AccountBlogLink";
import apiFetch from "@wordpress/api-fetch";

const { render, useState, useEffect } = wp.element;

const ManageConnections = () => {
  const [accounts, setAccounts] = useState([]);
  const [currentUserId, setUserId] = useState(0);

  useEffect(() => {
    apiFetch({ path: "/smolblog/v1/accounts/blogs" }).then(setAccounts);
    apiFetch({ path: "/wp/v2/users/me" }).then((response) =>
      setUserId(response.id)
    );
  }, []);

  return (
    <div>
      <table className="widefat striped fixed">
        <thead>
          <tr>
            <th>Account</th>
            <th>Push</th>
            <th>Pull</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
          {accounts.map((account) => (
            <AccountBlogLink account={account} currentUserId={currentUserId} />
          ))}
        </tbody>
      </table>
      <p style={{ textAlign: "center" }}>
        Icons from{" "}
        <a href="https://github.com/FortAwesome/Font-Awesome/tree/6.x/svgs/brands">
          FontAwesome
        </a>
      </p>
    </div>
  );
};

render(
  <ManageConnections />,
  document.getElementById("smolblog-social-connections-app")
);
